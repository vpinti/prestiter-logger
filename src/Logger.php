<?php

declare(strict_types=1);

namespace Logstitch;

use Throwable;

/**
 * Main logger class that dispatches log entries to multiple drivers.
 */
final class Logger
{
    /** @var DriverInterface[] */
    private array $drivers;

    /**
     * @param DriverInterface[] $drivers
     */
    public function __construct(array $drivers)
    {
        $this->drivers = $drivers;
    }

    /**
     * Log an entry to all configured drivers.
     */
    public function log(LogEntry $entry): void
    {
        foreach ($this->drivers as $driver) {
            try {
                $driver->send($entry);
            } catch (Throwable $e) {
                // Silently ignore driver exceptions to ensure logging never breaks the application
                error_log('[Logstitch] Driver exception: ' . $e->getMessage());
            }
        }
    }

    /**
     * Log an INFO level entry.
     */
    public function info(
        string $eventType,
        string $idProvider,
        string $step,
        string $source,
        string $flow,
        string $direction
    ): LogEntry {
        $entry = new LogEntry(
            LogEntry::LEVEL_INFO,
            $eventType,
            $idProvider,
            $step,
            $source,
            $flow,
            $direction
        );
        $this->log($entry);

        return $entry;
    }

    /**
     * Log a WARN level entry.
     */
    public function warn(
        string $eventType,
        string $idProvider,
        string $step,
        string $source,
        string $flow,
        string $direction
    ): LogEntry {
        $entry = new LogEntry(
            LogEntry::LEVEL_WARN,
            $eventType,
            $idProvider,
            $step,
            $source,
            $flow,
            $direction
        );
        $this->log($entry);

        return $entry;
    }

    /**
     * Log an ERROR level entry.
     */
    public function error(
        string $eventType,
        string $idProvider,
        string $step,
        string $source,
        string $flow,
        string $direction
    ): LogEntry {
        $entry = new LogEntry(
            LogEntry::LEVEL_ERROR,
            $eventType,
            $idProvider,
            $step,
            $source,
            $flow,
            $direction
        );
        $this->log($entry);

        return $entry;
    }

    /**
     * Log a DEBUG level entry.
     */
    public function debug(
        string $eventType,
        string $idProvider,
        string $step,
        string $source,
        string $flow,
        string $direction
    ): LogEntry {
        $entry = new LogEntry(
            LogEntry::LEVEL_DEBUG,
            $eventType,
            $idProvider,
            $step,
            $source,
            $flow,
            $direction
        );
        $this->log($entry);

        return $entry;
    }
}
