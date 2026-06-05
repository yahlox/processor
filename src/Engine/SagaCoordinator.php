<?php

declare(strict_types=1);

namespace Yahlox\Engine;

use Yahlox\Domain\ExecutionContext;
use RuntimeException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga pattern implementation for distributed transaction support.
 *
 * Provides compensating transaction (rollback) support for workflows.
 * When a step fails, all previously executed steps are rolled back in reverse order.
 *
 * @package Yahlox
 */
final class SagaCoordinator
{
    private array $compensations = [];
    private array $executedSteps = [];
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Register a compensation for a completed step.
     *
     * @param string $stepId Unique step identifier
     * @param callable $compensation Compensation function
     * @return void
     */
    public function registerCompensation(string $stepId, callable $compensation): void
    {
        $this->compensations[$stepId] = $compensation;
        $this->executedSteps[$stepId] = true;

        $this->logger->debug("Registered compensation for step: {$stepId}");
    }

    /**
     * Execute compensation (rollback) for all completed steps in reverse order.
     *
     * @param ExecutionContext $context Execution context
     * @return void
     */
    public function compensate(ExecutionContext $context): void
    {
        $this->logger->warning('Starting saga compensation (rollback)');

        // Execute in reverse order
        $stepIds = array_keys($this->executedSteps);
        $stepIds = array_reverse($stepIds);

        $compensationErrors = [];

        foreach ($stepIds as $stepId) {
            if (!isset($this->compensations[$stepId])) {
                continue;
            }

            try {
                $compensation = $this->compensations[$stepId];
                $compensation($context);

                $this->logger->info("Compensation completed for step: {$stepId}");
            } catch (RuntimeException $e) {
                $compensationErrors[$stepId] = $e->getMessage();

                $this->logger->error(
                    "Compensation failed for step: {$stepId}",
                    ['error' => $e->getMessage()]
                );
            }
        }

        if (!empty($compensationErrors)) {
            $errorSummary = implode('; ', $compensationErrors);
            throw new RuntimeException(
                "Saga compensation failed: {$errorSummary}"
            );
        }

        $this->logger->info('Saga compensation completed successfully');
    }

    /**
     * Mark a step as executed.
     *
     * @param string $stepId
     * @return void
     */
    public function markExecuted(string $stepId): void
    {
        $this->executedSteps[$stepId] = true;
    }

    /**
     * Check if step was executed.
     *
     * @param string $stepId
     * @return bool
     */
    public function wasExecuted(string $stepId): bool
    {
        return isset($this->executedSteps[$stepId]);
    }

    /**
     * Clear all registrations.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->compensations = [];
        $this->executedSteps = [];
    }
}

/**
 * Transaction manager for database operations.
 *
 * Manages database transactions and provides automatic rollback on failure.
 *
 * @package Yahlox
 */
final class TransactionManager
{
    private array $activeTransactions = [];
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Begin a transaction.
     *
     * @param string $connectionName Database connection name
     * @return void
     * @throws RuntimeException
     */
    public function begin(string $connectionName = 'default'): void
    {
        try {
            // Use Illuminate DB if available
            if (function_exists('\DB')) {
                \DB::connection($connectionName)->beginTransaction();
                $this->activeTransactions[$connectionName] = true;

                $this->logger->debug("Transaction started: {$connectionName}");
            }
        } catch (RuntimeException $e) {
            throw new RuntimeException(
                "Failed to begin transaction: {$e->getMessage()}"
            );
        }
    }

    /**
     * Commit a transaction.
     *
     * @param string $connectionName
     * @return void
     * @throws RuntimeException
     */
    public function commit(string $connectionName = 'default'): void
    {
        if (!$this->isActive($connectionName)) {
            return;
        }

        try {
            if (function_exists('\DB')) {
                \DB::connection($connectionName)->commit();
                unset($this->activeTransactions[$connectionName]);

                $this->logger->debug("Transaction committed: {$connectionName}");
            }
        } catch (RuntimeException $e) {
            throw new RuntimeException(
                "Failed to commit transaction: {$e->getMessage()}"
            );
        }
    }

    /**
     * Rollback a transaction.
     *
     * @param string $connectionName
     * @return void
     * @throws RuntimeException
     */
    public function rollback(string $connectionName = 'default'): void
    {
        if (!$this->isActive($connectionName)) {
            return;
        }

        try {
            if (function_exists('\DB')) {
                \DB::connection($connectionName)->rollBack();
                unset($this->activeTransactions[$connectionName]);

                $this->logger->warning("Transaction rolled back: {$connectionName}");
            }
        } catch (RuntimeException $e) {
            throw new RuntimeException(
                "Failed to rollback transaction: {$e->getMessage()}"
            );
        }
    }

    /**
     * Check if transaction is active.
     *
     * @param string $connectionName
     * @return bool
     */
    public function isActive(string $connectionName = 'default'): bool
    {
        return isset($this->activeTransactions[$connectionName]);
    }

    /**
     * Get all active transactions.
     *
     * @return array
     */
    public function getActiveTransactions(): array
    {
        return array_keys($this->activeTransactions);
    }

    /**
     * Rollback all active transactions.
     *
     * @return void
     */
    public function rollbackAll(): void
    {
        $connections = array_keys($this->activeTransactions);

        foreach ($connections as $connection) {
            try {
                $this->rollback($connection);
            } catch (RuntimeException $e) {
                $this->logger->error("Rollback failed for {$connection}: " . $e->getMessage());
            }
        }
    }

    /**
     * Execute a callback within a transaction.
     *
     * @param callable $callback
     * @param string $connectionName
     * @return mixed
     * @throws RuntimeException
     */
    public function execute(callable $callback, string $connectionName = 'default'): mixed
    {
        $this->begin($connectionName);

        try {
            $result = $callback();
            $this->commit($connectionName);
            return $result;
        } catch (RuntimeException $e) {
            $this->rollback($connectionName);
            throw $e;
        }
    }
}
