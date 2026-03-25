<?php

declare(strict_types=1);

namespace Logstitch\Tests\Driver;

use Logstitch\Driver\SyslogDriver;
use Logstitch\LogEntry;
use PHPUnit\Framework\TestCase;

final class SyslogDriverTest extends TestCase
{
    private const UNAVAILABLE_SOCKET = '/nonexistent/socket';

    public function testDoesNotThrowWhenSocketUnavailable(): void
    {
        $driver = new SyslogDriver('prestiter-test', self::UNAVAILABLE_SOCKET);

        $entry = new LogEntry(
            LogEntry::LEVEL_INFO,
            'test_event',
            'provider_123',
            'step_1',
            'test_source',
            'test_flow',
            'outbound'
        );

        // Must not throw — DriverInterface contract
        $driver->send($entry);
        $this->assertTrue(true);
    }

    public function testDoesNotThrowOnMultipleSends(): void
    {
        $driver = new SyslogDriver('prestiter-test', self::UNAVAILABLE_SOCKET);

        for ($i = 0; $i < 3; $i++) {
            $entry = new LogEntry(
                LogEntry::LEVEL_ERROR,
                'event_' . $i,
                'provider_' . $i,
                'step_' . $i,
                'source_' . $i,
                'flow_' . $i,
                'inbound'
            );

            $driver->send($entry);
        }

        $this->assertTrue(true);
    }

    public function testGetSocketPath(): void
    {
        $driver = new SyslogDriver('my-app', '/custom/socket');
        $this->assertEquals('/custom/socket', $driver->getSocketPath());
    }

    public function testSendWithDefaultConstructor(): void
    {
        $driver = new SyslogDriver();

        $entry = new LogEntry(
            LogEntry::LEVEL_DEBUG,
            'debug_event',
            'provider_1',
            'step_1',
            'source_1',
            'flow_1',
            'internal'
        );

        // Must not throw even if /dev/log is unavailable in CI
        $driver->send($entry);
        $this->assertEquals('/dev/log', $driver->getSocketPath());
    }

    public function testSendDoesNotWriteToAnyFile(): void
    {
        $driver = new SyslogDriver('prestiter-test', self::UNAVAILABLE_SOCKET);

        $tmpDir = sys_get_temp_dir();
        $rawBefore = glob($tmpDir . '/prestiter_*');
        $before = $rawBefore !== false ? $rawBefore : [];

        $entry = new LogEntry(
            LogEntry::LEVEL_WARN,
            'warn_event',
            'provider_456',
            'step_2',
            'source_2',
            'flow_2',
            'outbound'
        );

        $driver->send($entry);

        $rawAfter = glob($tmpDir . '/prestiter_*');
        $after = $rawAfter !== false ? $rawAfter : [];

        $this->assertCount(
            \count($before),
            $after,
            'SyslogDriver must not create any files — fallback is the consumer\'s responsibility'
        );
    }
}
