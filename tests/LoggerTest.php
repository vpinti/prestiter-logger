<?php

declare(strict_types=1);

namespace Logstitch\Tests;

use Logstitch\DriverInterface;
use Logstitch\LogEntry;
use Logstitch\Logger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class LoggerTest extends TestCase
{
    public function testAllDriversAreCalled(): void
    {
        $driver1 = $this->createMock(DriverInterface::class);
        $driver2 = $this->createMock(DriverInterface::class);
        $driver3 = $this->createMock(DriverInterface::class);

        $driver1->expects($this->once())->method('send');
        $driver2->expects($this->once())->method('send');
        $driver3->expects($this->once())->method('send');

        $logger = new Logger([$driver1, $driver2, $driver3]);

        $entry = new LogEntry(
            LogEntry::LEVEL_INFO,
            'test_event',
            'provider_123',
            'step_1',
            'test_source',
            'test_flow',
            'outbound'
        );

        $logger->log($entry);
    }

    public function testInfoMethodCreatesCorrectLogEntry(): void
    {
        $receivedEntry = null;

        $driver = $this->createMock(DriverInterface::class);
        $driver->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (LogEntry $entry) use (&$receivedEntry): void {
                $receivedEntry = $entry;
            });

        $logger = new Logger([$driver]);

        $logger->info(
            'api_call',
            'provider_456',
            'authentication',
            'api_gateway',
            'login_flow',
            'inbound'
        );

        $this->assertNotNull($receivedEntry);
        $this->assertEquals(LogEntry::LEVEL_INFO, $receivedEntry->getLevel());
        $this->assertEquals('api_call', $receivedEntry->getEventType());
        $this->assertEquals('provider_456', $receivedEntry->getIdProvider());
    }

    public function testWarnMethodCreatesCorrectLogEntry(): void
    {
        $receivedEntry = null;

        $driver = $this->createMock(DriverInterface::class);
        $driver->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (LogEntry $entry) use (&$receivedEntry): void {
                $receivedEntry = $entry;
            });

        $logger = new Logger([$driver]);

        $logger->warn(
            'rate_limit',
            'provider_789',
            'throttling',
            'rate_limiter',
            'api_flow',
            'outbound'
        );

        $this->assertNotNull($receivedEntry);
        $this->assertEquals(LogEntry::LEVEL_WARN, $receivedEntry->getLevel());
    }

    public function testErrorMethodCreatesCorrectLogEntry(): void
    {
        $receivedEntry = null;

        $driver = $this->createMock(DriverInterface::class);
        $driver->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (LogEntry $entry) use (&$receivedEntry): void {
                $receivedEntry = $entry;
            });

        $logger = new Logger([$driver]);

        $logger->error(
            'connection_failed',
            'provider_000',
            'retry',
            'http_client',
            'sync_flow',
            'outbound'
        );

        $this->assertNotNull($receivedEntry);
        $this->assertEquals(LogEntry::LEVEL_ERROR, $receivedEntry->getLevel());
    }

    public function testDebugMethodCreatesCorrectLogEntry(): void
    {
        $receivedEntry = null;

        $driver = $this->createMock(DriverInterface::class);
        $driver->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (LogEntry $entry) use (&$receivedEntry): void {
                $receivedEntry = $entry;
            });

        $logger = new Logger([$driver]);

        $logger->debug(
            'request_details',
            'provider_111',
            'tracing',
            'debugger',
            'debug_flow',
            'internal'
        );

        $this->assertNotNull($receivedEntry);
        $this->assertEquals(LogEntry::LEVEL_DEBUG, $receivedEntry->getLevel());
    }

    public function testLoggerContinuesWhenDriverThrowsException(): void
    {
        $driver1 = $this->createMock(DriverInterface::class);
        $driver2 = $this->createMock(DriverInterface::class);

        $driver1->expects($this->once())
            ->method('send')
            ->willThrowException(new RuntimeException('Driver 1 failed'));

        // Driver 2 should still be called even if driver 1 throws
        $driver2->expects($this->once())->method('send');

        $logger = new Logger([$driver1, $driver2]);

        $entry = new LogEntry(
            LogEntry::LEVEL_INFO,
            'test_event',
            'provider_123',
            'step_1',
            'test_source',
            'test_flow',
            'outbound'
        );

        // Should not throw
        $logger->log($entry);
    }

    public function testLoggerWithEmptyDriversArray(): void
    {
        $logger = new Logger([]);

        $entry = new LogEntry(
            LogEntry::LEVEL_INFO,
            'test_event',
            'provider_123',
            'step_1',
            'test_source',
            'test_flow',
            'outbound'
        );

        // Should not throw
        $logger->log($entry);

        $this->assertTrue(true); // If we reach here, the test passed
    }
}
