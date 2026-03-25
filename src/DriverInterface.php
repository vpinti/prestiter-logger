<?php

declare(strict_types=1);

namespace Logstitch;

/**
 * Interface for log drivers.
 *
 * Implementations must never throw exceptions to the caller.
 * Transport errors should be handled internally.
 */
interface DriverInterface
{
    /**
     * Send a log entry to the destination.
     *
     * This method must never throw exceptions.
     * Any transport errors should be handled internally.
     */
    public function send(LogEntry $entry): void;
}
