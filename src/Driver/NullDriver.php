<?php

declare(strict_types=1);

namespace Logstitch\Driver;

use Logstitch\DriverInterface;
use Logstitch\LogEntry;

/**
 * Null driver that does nothing.
 * Useful for testing environments.
 */
final class NullDriver implements DriverInterface
{
    public function send(LogEntry $entry): void
    {
        // Intentionally does nothing
    }
}
