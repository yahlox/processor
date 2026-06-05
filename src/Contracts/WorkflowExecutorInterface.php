<?php

declare(strict_types=1);

namespace Yahlox\Contracts;

use Yahlox\Domain\Workflow;
use Yahlox\Domain\ExecutionContext;

/**
 * Defines the contract for workflow executor implementations.
 *
 * @package Yahlox
 */
interface WorkflowExecutorInterface
{
    public function execute(
        Workflow $workflow,
        ExecutionContext $context
    ): void;
}
