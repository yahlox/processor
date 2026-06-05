<?php

declare(strict_types=1);

namespace Yahlox\Utils;

use Throwable;

/**
 * Handles retry logic with exponential backoff for resilient operations.
 *
 * Features:
 * - Configurable max attempts
 * - Exponential backoff with multiplier
 * - Callback for retry notifications
 *
 * @package Yahlox
 */
final class RetryPolicy
{
    public function __construct(
        private readonly int $maxAttempts = 3,
        private readonly int $initialDelayMs = 100,
        private readonly float $backoffMultiplier = 2.0
    ) {
    }

    /**
     * Execute a callable with retry logic.
     *
     * @param callable $operation The operation to retry
     * @param callable|null $onRetry Optional callback when retry is attempted
     * @return mixed The result of the operation
     * @throws Throwable If all attempts fail
     */
    public function execute(callable $operation, ?callable $onRetry = null): mixed
    {
        $lastException = null;
        $delayMs = $this->initialDelayMs;

        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            try {
                return $operation();
            } catch (Throwable $e) {
                $lastException = $e;

                if ($attempt < $this->maxAttempts) {
                    if ($onRetry !== null) {
                        $onRetry($attempt, $delayMs);
                    }

                    // Sleep for the calculated delay
                    usleep($delayMs * 1000); // Convert ms to microseconds

                    // Calculate next delay with exponential backoff
                    $delayMs = (int)($delayMs * $this->backoffMultiplier);
                }
            }
        }

        throw $lastException ?? new \RuntimeException('Operation failed after ' . $this->maxAttempts . ' attempts');
    }
}
