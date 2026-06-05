<?php

declare(strict_types=1);

namespace Yahlox\Contracts;

use Yahlox\Domain\ExecutionContext;
use Throwable;

/**
 * Coordinates distributed transactions with compensation (saga pattern).
 *
 * @package Yahlox
 */
interface SagaCoordinatorInterface
{
    /**
     * Register a compensating action for a transaction.
     *
     * @param string $transactionId Unique transaction identifier
     * @param string $stepId Unique step identifier
     * @param callable $compensation Compensation logic to execute on rollback
     * @return void
     */
    public function registerCompensation(string $transactionId, string $stepId, callable $compensation): void;

    /**
     * Execute a transaction step.
     *
     * @param string $transactionId Unique transaction identifier
     * @param string $stepId Unique step identifier
     * @param callable $operation Operation to execute
     * @return mixed Operation result
     * @throws Throwable
     */
    public function executeStep(string $transactionId, string $stepId, callable $operation): mixed;

    /**
     * Rollback all compensation steps for a transaction.
     *
     * @param string $transactionId Unique transaction identifier
     * @return void
     */
    public function rollback(string $transactionId): void;

    /**
     * Commit transaction (clear compensation steps).
     *
     * @param string $transactionId Unique transaction identifier
     * @return void
     */
    public function commit(string $transactionId): void;
}
