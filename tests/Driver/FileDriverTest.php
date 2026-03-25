<?php

declare(strict_types=1);

namespace Logstitch\Tests\Driver;

use Logstitch\Driver\FileDriver;
use Logstitch\LogEntry;
use PHPUnit\Framework\TestCase;

final class FileDriverTest extends TestCase
{
    private string $testFilePath;

    protected function setUp(): void
    {
        $this->testFilePath = sys_get_temp_dir() . '/prestiter_logger_test_' . uniqid() . '.log';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
    }

    public function testFileIsCreatedAndLogWritten(): void
    {
        $driver = new FileDriver($this->testFilePath);

        $entry = new LogEntry(
            LogEntry::LEVEL_INFO,
            'test_event',
            'provider_123',
            'step_1',
            'test_source',
            'test_flow',
            'outbound'
        );

        $driver->send($entry);

        $this->assertFileExists($this->testFilePath);

        $content = file_get_contents($this->testFilePath);
        $this->assertIsString($content);
        $this->assertNotEmpty($content);

        $decoded = json_decode(trim($content), true);
        $this->assertIsArray($decoded);
        $this->assertEquals('INFO', $decoded['level']);
        $this->assertEquals('test_event', $decoded['event_type']);
        $this->assertEquals('provider_123', $decoded['id_provider']);
    }

    public function testMultipleEntriesAreAppended(): void
    {
        $driver = new FileDriver($this->testFilePath);

        $entry1 = new LogEntry(
            LogEntry::LEVEL_INFO,
            'event_1',
            'provider_1',
            'step_1',
            'source_1',
            'flow_1',
            'outbound'
        );

        $entry2 = new LogEntry(
            LogEntry::LEVEL_ERROR,
            'event_2',
            'provider_2',
            'step_2',
            'source_2',
            'flow_2',
            'inbound'
        );

        $driver->send($entry1);
        $driver->send($entry2);

        $content = file_get_contents($this->testFilePath);
        $this->assertIsString($content);
        $lines = array_filter(explode(PHP_EOL, $content));

        $this->assertCount(2, $lines);

        $decoded1 = json_decode($lines[0], true);
        $decoded2 = json_decode($lines[1], true);

        $this->assertEquals('event_1', $decoded1['event_type']);
        $this->assertEquals('event_2', $decoded2['event_type']);
        $this->assertEquals('INFO', $decoded1['level']);
        $this->assertEquals('ERROR', $decoded2['level']);
    }

    public function testOptionalFieldsAreIncluded(): void
    {
        $driver = new FileDriver($this->testFilePath);

        $entry = new LogEntry(
            LogEntry::LEVEL_ERROR,
            'api_error',
            'provider_456',
            'request',
            'http_client',
            'api_flow',
            'outbound'
        );

        $entry->setHttpStatus(500)
            ->setResponseMs(1234)
            ->setApiVersion('v2')
            ->setPayload(['key' => 'value'])
            ->setErrorMessage('Internal Server Error')
            ->setErrorClass('HttpException')
            ->setStackTrace('at line 42');

        $driver->send($entry);

        $content = file_get_contents($this->testFilePath);
        $this->assertIsString($content);
        $decoded = json_decode(trim($content), true);

        $this->assertEquals(500, $decoded['http_status']);
        $this->assertEquals(1234, $decoded['response_ms']);
        $this->assertEquals('v2', $decoded['api_version']);
        $this->assertEquals(['key' => 'value'], $decoded['payload']);
        $this->assertEquals('Internal Server Error', $decoded['error_message']);
        $this->assertEquals('HttpException', $decoded['error_class']);
        $this->assertEquals('at line 42', $decoded['stack_trace']);
    }

    public function testGetFilePath(): void
    {
        $driver = new FileDriver($this->testFilePath);
        $this->assertEquals($this->testFilePath, $driver->getFilePath());
    }

    public function testJsonLinesFormat(): void
    {
        $driver = new FileDriver($this->testFilePath);

        for ($i = 0; $i < 5; $i++) {
            $entry = new LogEntry(
                LogEntry::LEVEL_DEBUG,
                "event_$i",
                "provider_$i",
                "step_$i",
                "source_$i",
                "flow_$i",
                'internal'
            );
            $driver->send($entry);
        }

        $content = file_get_contents($this->testFilePath);
        $this->assertIsString($content);
        $lines = array_filter(explode(PHP_EOL, $content));

        $this->assertCount(5, $lines);

        foreach ($lines as $index => $line) {
            $decoded = json_decode($line, true);
            $this->assertIsArray($decoded, "Line $index is not valid JSON");
            $this->assertEquals("event_$index", $decoded['event_type']);
        }
    }
}
