<?php

declare(strict_types=1);

namespace Yahlox\Contracts;

use Throwable;

/**
 * Manages database transactions with automatic rollback on errors.
 *
 * @package Yahlox
 */
interface TransactionManagerInterface
{
    /**
     * Begin a transaction.
     *
     * @return void
     */
    public function begin(): void;

    /**
     * Execute a callback within a transaction.
     *
     * @param callable $callback Operation to execute
     * @return mixed Callback result
     * @throws Throwable
     */
    public function execute(callable $callback): mixed;

    /**
     * Commit the current transaction.
     *
     * @return void
     */
    public function commit(): void;

    /**
     * Rollback the current transaction.
     *
     * @return void
     */
    public function rollback(): void;

    /**
     * Check if a transaction is currently active.
     *
     * @return bool
     */
    public function isActive(): bool;
}
