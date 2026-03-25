# Logstitch

[![CI](https://github.com/Carmati-CRM/logstitch/actions/workflows/ci.yml/badge.svg)](https://github.com/Carmati-CRM/logstitch/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-8892BF)](composer.json)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

A structured logging library for PHP that is agnostic from the destination SaaS. Your application code doesn't need to know where logs are being sent.

## Requirements

- PHP >= 7.4
- cURL extension (for NewRelicDriver)
- `ext-sockets` (optional, recommended for SyslogDriver DGRAM transport)

## Installation

Add the repository to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Carmati-CRM/logstitch"
        }
    ],
    "require": {
        "logstitch/logstitch": "dev-main"
    }
}
```

Then run:

```bash
composer update
```

## Usage

### Basic Setup with NewRelic + File Fallback

```php
<?php

use Logstitch\Logger;
use Logstitch\LogEntry;
use Logstitch\Driver\NewRelicDriver;
use Logstitch\Driver\FileDriver;

// Create drivers
$newRelicDriver = new NewRelicDriver('your-new-relic-api-key');
$fileDriver = new FileDriver('/var/log/app/prestiter.log');

// Create logger with multiple drivers
// Logs will be sent to all drivers in sequence
$logger = new Logger([
    $newRelicDriver,
    $fileDriver,  // Acts as fallback if NewRelic fails silently
]);

// Log an INFO event
$logger->info(
    'api_request',           // event_type
    'provider_123',          // id_provider
    'authentication',        // step
    'api_gateway',           // source
    'login_flow',            // flow
    'outbound'               // direction
);

// Log an ERROR with additional details
$entry = $logger->error(
    'connection_failed',
    'provider_456',
    'retry',
    'http_client',
    'sync_flow',
    'outbound'
);

// Add optional fields (fluent interface)
$entry->setHttpStatus(500)
    ->setResponseMs(1234)
    ->setApiVersion('v2')
    ->setPayload(['request_id' => 'abc123'])
    ->setErrorMessage('Connection refused')
    ->setErrorClass('ConnectionException')
    ->setStackTrace($exception->getTraceAsString());

// Send the entry with additional fields
$logger->log($entry);
```

### Using LogEntry Directly

```php
<?php

use Logstitch\LogEntry;

$entry = new LogEntry(
    LogEntry::LEVEL_WARN,
    'rate_limit_exceeded',
    'provider_789',
    'throttling',
    'rate_limiter',
    'api_flow',
    'inbound'
);

$entry->setHttpStatus(429)
    ->setResponseMs(50)
    ->setPayload(['retry_after' => 60]);

$logger->log($entry);
```

### Environment Configuration

The `environment` field is automatically set from the `APP_ENV` environment variable. If not set, it defaults to `"production"`.

```bash
export APP_ENV=staging
```

### Testing Environment

Use `NullDriver` in test environments:

```php
<?php

use Logstitch\Logger;
use Logstitch\Driver\NullDriver;

$logger = new Logger([new NullDriver()]);

// Logs are silently discarded
$logger->info('test_event', 'test', 'test', 'test', 'test', 'test');
```

## Available Drivers

### NewRelicDriver

Sends logs to New Relic's Log API (`https://log-api.newrelic.com/log/v1`).

- Timeout: 3 seconds
- Expected response: HTTP 202
- Errors are logged to `error_log()` silently

```php
$driver = new NewRelicDriver('your-api-key');
```

### FileDriver

Writes logs to a file in JSON Lines format (one JSON object per line).

- Uses `FILE_APPEND` and `LOCK_EX` for safe concurrent writes
- Errors are logged to `error_log()` silently

```php
$driver = new FileDriver('/path/to/logs/app.log');
```

### SyslogDriver

Sends logs to a local rsyslog via Unix socket in **RFC 5424** format, with the full JSON payload in the MSG field. rsyslog can then forward to an external syslog-ng server or any other destination.

Transport chain (first that succeeds wins):
1. **DGRAM** via `ext-sockets` — standard `/dev/log` on Linux with rsyslog
2. **STREAM** via `stream_socket_client` — systemd-managed socket (SEQPACKET)
3. **Silent failure** — `error_log()` warning, no exception thrown

The driver does not write a file fallback on its own. Use the `Logger` fan-out to compose a `FileDriver` alongside it if double-write resilience is needed (see examples below).

```php
use Logstitch\Driver\SyslogDriver;

// Minimal: app name only, defaults to /dev/log
$driver = new SyslogDriver('my-app');

// Explicit socket path
$driver = new SyslogDriver('my-app', '/dev/log');

echo $driver->getSocketPath(); // /dev/log
```

**Fan-out examples** — the consumer decides the strategy:

```php
use Logstitch\Logger;
use Logstitch\Driver\SyslogDriver;
use Logstitch\Driver\FileDriver;

// rsyslog only — trust rsyslog reliability
$logger = new Logger([new SyslogDriver('my-app')]);

// File only — external server reads the directory
$logger = new Logger([new FileDriver('/var/log/my-app/prestiter.log')]);

// Always write to both (maximum resilience, consumer's choice)
$logger = new Logger([
    new SyslogDriver('my-app'),
    new FileDriver('/var/log/my-app/prestiter.log'),
]);
```

The RFC 5424 message format produced:

```
<PRI>1 TIMESTAMP HOSTNAME APP-NAME PID MSGID - {"timestamp":...,"level":"INFO",...}
```

Where `PRI = (LOCAL0_facility << 3) | severity` (e.g. `<134>` for INFO).

Verify on the receiving server:

```bash
sudo tail -f /var/log/syslog
```

### NullDriver

Does nothing. Useful for testing or disabling logging.

```php
$driver = new NullDriver();
```

## LogEntry Fields

### Required Fields (set in constructor)

| Field | Type | Description |
|-------|------|-------------|
| `timestamp` | int | UTC timestamp in milliseconds (auto-generated) |
| `level` | string | INFO, WARN, ERROR, or DEBUG |
| `event_type` | string | Type of event being logged |
| `id_provider` | string | Provider identifier |
| `step` | string | Current step in the process |
| `source` | string | Source of the log |
| `flow` | string | Flow/workflow name |
| `direction` | string | Direction (inbound/outbound/internal) |
| `environment` | string | Environment name (auto from APP_ENV) |

### Optional Fields (set via setters)

| Field | Type | Description |
|-------|------|-------------|
| `http_status` | int | HTTP status code |
| `response_ms` | int | Response time in milliseconds |
| `api_version` | string | API version |
| `payload` | array | Additional data |
| `error_message` | string | Error message |
| `error_class` | string | Exception/error class name |
| `stack_trace` | string | Stack trace |

## Error Handling

The library is designed to never throw exceptions to the caller. All transport errors are handled internally and logged to PHP's `error_log()`.

This ensures that logging failures never break your application.

## Development

### Using Docker (no PHP required)

If you don't have PHP installed locally, you can use Docker:

```bash
# Build the image
docker compose build

# Run tests
docker compose run --rm php composer test

# Run all checks (analysis, linting, tests)
docker compose run --rm php composer check

# Run static analysis
docker compose run --rm php composer analyse

# Fix code style
docker compose run --rm php composer fix

# Open a shell in the container
docker compose run --rm php sh
```

### Local Development (with PHP)

#### Running Tests

```bash
composer install
composer test
```

### Static Analysis

The project uses PHPStan and Psalm for static analysis:

```bash
# Run PHPStan (level max)
composer phpstan

# Run Psalm (level 1 - strictest)
composer psalm

# Run both analyzers
composer analyse
```

### Code Style

The project follows PSR-12 coding standard and uses PHP_CodeSniffer and PHP-CS-Fixer:

```bash
# Check code style with PHP_CodeSniffer
composer phpcs

# Check code style with PHP-CS-Fixer (dry run)
composer cs-check

# Run all linters
composer lint

# Auto-fix code style issues
composer fix
```

### Run All Checks

```bash
# Run static analysis, linting, and tests
composer check
```

## License

MIT
