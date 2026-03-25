<?php

declare(strict_types=1);

namespace Logstitch\Driver;

use Logstitch\DriverInterface;
use Logstitch\LogEntry;
use Throwable;

/**
 * Driver for writing logs to a file in JSON Lines format.
 */
final class FileDriver implements DriverInterface
{
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function send(LogEntry $entry): void
    {
        try {
            $line = $entry->toJson() . PHP_EOL;

            $result = file_put_contents(
                $this->filePath,
                $line,
                FILE_APPEND | LOCK_EX
            );

            if ($result === false) {
                error_log('[Logstitch] Failed to write to file: ' . $this->filePath);
            }
        } catch (Throwable $e) {
            error_log('[Logstitch] Exception writing to file: ' . $e->getMessage());
        }
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }
}
