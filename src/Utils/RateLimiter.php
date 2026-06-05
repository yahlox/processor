<?php

declare(strict_types=1);

namespace Yahlox\Utils;

use RuntimeException;

/**
 * Rate limiter and retry policy utilities for long-running operations.
 *
 * @package Yahlox
 */
final class RateLimiter
{
    private array $counters = [];
    private array $resetTimes = [];

    /**
     * Check if an operation is allowed based on rate limit.
     *
     * @param string $key Rate limit key (e.g., 'email_sends', 'api_calls')
     * @param int $limit Maximum operations per window
     * @param int $windowSeconds Time window in seconds
     * @return bool True if operation is allowed
     */
    public function isAllowed(string $key, int $limit, int $windowSeconds): bool
    {
        $now = time();
        
        // Initialize if not exists
        if (!isset($this->counters[$key])) {
            $this->counters[$key] = 0;
            $this->resetTimes[$key] = $now + $windowSeconds;
            return true;
        }

        // Reset counter if window expired
        if ($now >= $this->resetTimes[$key]) {
            $this->counters[$key] = 0;
            $this->resetTimes[$key] = $now + $windowSeconds;
            return true;
        }

        // Check limit
        if ($this->counters[$key] < $limit) {
            $this->counters[$key]++;
            return true;
        }

        return false;
    }

    /**
     * Get remaining operations in current window.
     *
     * @param string $key Rate limit key
     * @param int $limit Maximum operations per window
     * @return int Remaining operations
     */
    public function getRemaining(string $key, int $limit): int
    {
        return max(0, $limit - ($this->counters[$key] ?? 0));
    }

    /**
     * Reset a rate limit.
     *
     * @param string $key Rate limit key
     * @return void
     */
    public function reset(string $key): void
    {
        unset($this->counters[$key], $this->resetTimes[$key]);
    }
}

/**
 * Retry policy executor with exponential backoff.
 *
 * @package Yahlox
 */
final class RetryPolicy
{
    private int $maxAttempts = 3;
    private int $initialDelayMs = 100;
    private float $backoffMultiplier = 2.0;
    private int $maxDelayMs = 30000;
    private array $retryableExceptions = [
        'RuntimeException',
        'PDOException',
        'Throwable',
    ];

    public function __construct(
        int $maxAttempts = 3,
        int $initialDelayMs = 100,
        float $backoffMultiplier = 2.0,
        int $maxDelayMs = 30000
    ) {
        $this->maxAttempts = $maxAttempts;
        $this->initialDelayMs = $initialDelayMs;
        $this->backoffMultiplier = $backoffMultiplier;
        $this->maxDelayMs = $maxDelayMs;
    }

    /**
     * Execute a callable with retry logic.
     *
     * @param callable $operation Operation to retry
     * @param callable|null $onRetry Callback on retry (receives attempt number)
     * @return mixed Result of operation
     * @throws RuntimeException
     */
    public function execute(callable $operation, ?callable $onRetry = null): mixed
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            try {
                return $operation();
            } catch (\Throwable $e) {
                $lastException = $e;

                if ($attempt < $this->maxAttempts && $this->shouldRetry($e)) {
                    $delayMs = $this->calculateDelay($attempt);
                    
                    if ($onRetry !== null) {
                        $onRetry($attempt, $delayMs, $e);
                    }

                    usleep($delayMs * 1000);
                } else {
                    throw $e;
                }
            }
        }

        throw $lastException;
    }

    /**
     * Determine if exception is retryable.
     *
     * @param \Throwable $exception
     * @return bool
     */
    private function shouldRetry(\Throwable $exception): bool
    {
        foreach ($this->retryableExceptions as $exceptionClass) {
            if ($exception instanceof $exceptionClass) {
                return true;
            }
        }
        return false;
    }

    /**
     * Calculate delay with exponential backoff.
     *
     * @param int $attempt Current attempt number
     * @return int Delay in milliseconds
     */
    private function calculateDelay(int $attempt): int
    {
        $delay = (int)($this->initialDelayMs * pow($this->backoffMultiplier, $attempt - 1));
        return min($delay, $this->maxDelayMs);
    }

    /**
     * Add a retryable exception class.
     *
     * @param string $exceptionClass Fully qualified exception class name
     * @return void
     */
    public function addRetryableException(string $exceptionClass): void
    {
        if (!in_array($exceptionClass, $this->retryableExceptions, true)) {
            $this->retryableExceptions[] = $exceptionClass;
        }
    }
}

/**
 * Timeout handler for long-running operations.
 *
 * @package Yahlox
 */
final class TimeoutHandler
{
    private float $startTime = 0;
    private ?int $timeoutSeconds = null;

    /**
     * Create a new timeout handler.
     *
     * @param int|null $timeoutSeconds Timeout in seconds, null for no timeout
     */
    public function __construct(?int $timeoutSeconds = null)
    {
        $this->timeoutSeconds = $timeoutSeconds;
        $this->startTime = microtime(true);
    }

    /**
     * Check if operation has exceeded timeout.
     *
     * @return bool True if timeout exceeded
     */
    public function isExceeded(): bool
    {
        if ($this->timeoutSeconds === null) {
            return false;
        }

        return (microtime(true) - $this->startTime) > $this->timeoutSeconds;
    }

    /**
     * Get elapsed time in seconds.
     *
     * @return float Elapsed time
     */
    public function getElapsed(): float
    {
        return microtime(true) - $this->startTime;
    }

    /**
     * Get remaining time in seconds.
     *
     * @return float|null Remaining time or null if no timeout
     */
    public function getRemaining(): ?float
    {
        if ($this->timeoutSeconds === null) {
            return null;
        }

        return max(0, $this->timeoutSeconds - $this->getElapsed());
    }

    /**
     * Throw if timeout exceeded.
     *
     * @return void
     * @throws RuntimeException
     */
    public function throwIfExceeded(string $operationName = 'Operation'): void
    {
        if ($this->isExceeded()) {
            throw new RuntimeException(
                "{$operationName} exceeded timeout of {$this->timeoutSeconds}s"
            );
        }
    }
}
