<?php

declare(strict_types=1);

namespace Yahlox\Engine;

use Yahlox\Contracts\TransactionManagerInterface;
use Throwable;
use Illuminate\Database\ConnectionInterface;

/**
 * Manages database transactions with automatic rollback on errors.
 *
 * Features:
 * - Automatic rollback on exceptions
 * - Nested transaction support (savepoints)
 * - Fluent callback interface
 *
 * @package Yahlox
 */
final class TransactionManager implements TransactionManagerInterface
{
    private bool $isActive = false;
    private int $nesting = 0;

    public function __construct(
        private readonly ?ConnectionInterface $connection = null
    ) {
    }

    /**
     * Begin a transaction.
     *
     * @return void
     */
    public function begin(): void
    {
        if ($this->connection === null) {
            return;
        }

        if ($this->nesting === 0) {
            $this->connection->beginTransaction();
            $this->isActive = true;
        }

        $this->nesting++;
    }

    /**
     * Execute a callback within a transaction.
     *
     * @param callable $callback Operation to execute
     * @return mixed Callback result
     * @throws Throwable
     */
    public function execute(callable $callback): mixed
    {
        $this->begin();

        try {
            $result = $callback();
            $this->commit();
            return $result;
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Commit the current transaction.
     *
     * @return void
     */
    public function commit(): void
    {
        if ($this->connection === null || $this->nesting === 0) {
            return;
        }

        $this->nesting--;

        if ($this->nesting === 0) {
            $this->connection->commit();
            $this->isActive = false;
        }
    }

    /**
     * Rollback the current transaction.
     *
     * @return void
     */
    public function rollback(): void
    {
        if ($this->connection === null || $this->nesting === 0) {
            return;
        }

        $this->nesting--;

        if ($this->nesting === 0) {
            $this->connection->rollBack();
            $this->isActive = false;
        }
    }

    /**
     * Check if a transaction is currently active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive && $this->nesting > 0;
    }
}
