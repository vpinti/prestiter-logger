<?php

declare(strict_types=1);

namespace Logstitch\Driver;

use DateTimeImmutable;
use DateTimeZone;
use Logstitch\DriverInterface;
use Logstitch\LogEntry;
use Throwable;

/**
 * Driver for sending logs to a local rsyslog via Unix socket in RFC 5424 format.
 *
 * Tries DGRAM transport first (standard rsyslog), then STREAM (systemd-managed socket).
 * If both fail, logs a warning via error_log and returns silently.
 *
 * The consumer is responsible for composing fallback strategies (e.g. adding a
 * FileDriver alongside this driver in the Logger constructor).
 */
final class SyslogDriver implements DriverInterface
{
    private const RFC5424_VERSION = 1;

    private const FACILITY = 16; // LOCAL0

    /** @var array<string, int> */
    private const SEVERITY = [
        LogEntry::LEVEL_DEBUG => 7,
        LogEntry::LEVEL_INFO => 6,
        LogEntry::LEVEL_WARN => 4,
        LogEntry::LEVEL_ERROR => 3,
    ];

    private string $appName;

    private string $socketPath;

    public function __construct(
        string $appName = 'logstitch',
        string $socketPath = '/dev/log'
    ) {
        $this->appName = $appName;
        $this->socketPath = $socketPath;
    }

    public function send(LogEntry $entry): void
    {
        try {
            $message = $this->buildMessage($entry);

            if ($this->sendViaDgram($message)) {
                return;
            }

            if ($this->sendViaStream($message)) {
                return;
            }

            error_log(
                '[Logstitch] SyslogDriver: could not deliver log to ' . $this->socketPath
                . ' (both DGRAM and STREAM failed)'
            );
        } catch (Throwable $e) {
            error_log('[Logstitch] SyslogDriver: ' . $e->getMessage());
        }
    }

    public function getSocketPath(): string
    {
        return $this->socketPath;
    }

    private function buildMessage(LogEntry $entry): string
    {
        $pri = (self::FACILITY << 3) | $this->resolveSeverity($entry->getLevel());
        $ts = $this->formatTimestamp($entry->getTimestamp());
        $host = $this->sanitize((string) gethostname(), 255);
        $app = $this->sanitize($this->appName, 48);
        $rawPid = getmypid();
        $pid = (string) ($rawPid !== false ? $rawPid : 0);
        $msgId = $this->sanitize($entry->getEventType(), 32);

        return \sprintf(
            '<%d>%d %s %s %s %s %s - %s',
            $pri,
            self::RFC5424_VERSION,
            $ts,
            $host,
            $app,
            $pid,
            $msgId,
            $entry->toJson()
        );
    }

    private function resolveSeverity(string $level): int
    {
        return self::SEVERITY[$level] ?? 6;
    }

    private function formatTimestamp(int $timestampMs): string
    {
        $seconds = intdiv($timestampMs, 1000);
        $millis = $timestampMs % 1000;

        return (new DateTimeImmutable('@' . $seconds))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s') . \sprintf('.%03dZ', $millis);
    }

    private function sanitize(string $value, int $maxLen): string
    {
        $clean = preg_replace('/[\x00-\x20\x7f]/', '_', $value) ?? '-';
        $clean = substr($clean, 0, $maxLen);

        return $clean !== '' ? $clean : '-';
    }

    private function sendViaDgram(string $message): bool
    {
        if (!\extension_loaded('sockets') || !file_exists($this->socketPath)) {
            return false;
        }

        $sock = @socket_create(AF_UNIX, SOCK_DGRAM, 0);

        if ($sock === false) {
            return false;
        }

        $result = @socket_sendto($sock, $message, \strlen($message), 0, $this->socketPath);
        socket_close($sock);

        return $result !== false && $result > 0;
    }

    private function sendViaStream(string $message): bool
    {
        $errno = 0;
        $errstr = '';

        $socket = @stream_socket_client('unix://' . $this->socketPath, $errno, $errstr, 1.0);

        if ($socket === false) {
            return false;
        }

        $written = @fwrite($socket, $message);
        fclose($socket);

        return $written !== false && $written > 0;
    }
}
