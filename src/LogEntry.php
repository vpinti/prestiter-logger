<?php

declare(strict_types=1);

namespace Logstitch;

/**
 * Value object representing a single log event.
 */
final class LogEntry
{
    public const LEVEL_INFO = 'INFO';

    public const LEVEL_WARN = 'WARN';

    public const LEVEL_ERROR = 'ERROR';

    public const LEVEL_DEBUG = 'DEBUG';

    private int $timestamp;

    private string $level;

    private string $eventType;

    private string $idProvider;

    private string $step;

    private string $source;

    private string $flow;

    private string $direction;

    private string $environment;

    private ?int $httpStatus = null;

    private ?int $responseMs = null;

    private ?string $apiVersion = null;

    /** @var array<string, mixed>|null */
    private ?array $payload = null;

    private ?string $errorMessage = null;

    private ?string $errorClass = null;

    private ?string $stackTrace = null;

    public function __construct(
        string $level,
        string $eventType,
        string $idProvider,
        string $step,
        string $source,
        string $flow,
        string $direction
    ) {
        $this->timestamp = (int) (microtime(true) * 1000);
        $this->level = $level;
        $this->eventType = $eventType;
        $this->idProvider = $idProvider;
        $this->step = $step;
        $this->source = $source;
        $this->flow = $flow;
        $this->direction = $direction;
        $appEnv = getenv('APP_ENV');
        $this->environment = $appEnv !== false ? $appEnv : 'production';
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getIdProvider(): string
    {
        return $this->idProvider;
    }

    public function getStep(): string
    {
        return $this->step;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getFlow(): string
    {
        return $this->flow;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function setHttpStatus(?int $httpStatus): self
    {
        $this->httpStatus = $httpStatus;

        return $this;
    }

    public function getResponseMs(): ?int
    {
        return $this->responseMs;
    }

    public function setResponseMs(?int $responseMs): self
    {
        $this->responseMs = $responseMs;

        return $this;
    }

    public function getApiVersion(): ?string
    {
        return $this->apiVersion;
    }

    public function setApiVersion(?string $apiVersion): self
    {
        $this->apiVersion = $apiVersion;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    public function setPayload(?array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getErrorClass(): ?string
    {
        return $this->errorClass;
    }

    public function setErrorClass(?string $errorClass): self
    {
        $this->errorClass = $errorClass;

        return $this;
    }

    public function getStackTrace(): ?string
    {
        return $this->stackTrace;
    }

    public function setStackTrace(?string $stackTrace): self
    {
        $this->stackTrace = $stackTrace;

        return $this;
    }

    /**
     * Convert the log entry to an array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'timestamp' => $this->timestamp,
            'level' => $this->level,
            'event_type' => $this->eventType,
            'id_provider' => $this->idProvider,
            'step' => $this->step,
            'source' => $this->source,
            'flow' => $this->flow,
            'direction' => $this->direction,
            'environment' => $this->environment,
        ];

        if ($this->httpStatus !== null) {
            $data['http_status'] = $this->httpStatus;
        }

        if ($this->responseMs !== null) {
            $data['response_ms'] = $this->responseMs;
        }

        if ($this->apiVersion !== null) {
            $data['api_version'] = $this->apiVersion;
        }

        if ($this->payload !== null) {
            $data['payload'] = $this->payload;
        }

        if ($this->errorMessage !== null) {
            $data['error_message'] = $this->errorMessage;
        }

        if ($this->errorClass !== null) {
            $data['error_class'] = $this->errorClass;
        }

        if ($this->stackTrace !== null) {
            $data['stack_trace'] = $this->stackTrace;
        }

        return $data;
    }

    /**
     * Convert the log entry to JSON.
     */
    public function toJson(): string
    {
        $json = json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json !== false ? $json : '{}';
    }
}
