<?php

declare(strict_types=1);

namespace Yahlox\Contracts;

use Throwable;

/**
 * Executes operations with configurable retry logic and exponential backoff.
 *
 * @package Yahlox
 */
interface RetryPolicyInterface
{
    /**
     * Execute a callable with retry logic.
     *
     * @param callable $operation The operation to retry
     * @param callable|null $onRetry Optional callback when retry is attempted
     * @return mixed The result of the operation
     * @throws Throwable If all attempts fail
     */
    public function execute(callable $operation, ?callable $onRetry = null): mixed;
}
