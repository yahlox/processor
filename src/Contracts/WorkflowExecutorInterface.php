<?php

declare(strict_types=1);

namespace Yahlox\Contracts;

use Yahlox\Domain\Workflow;
use Yahlox\Domain\ExecutionContext;

interface WorkflowExecutorInterface
{
    public function execute(
        Workflow $workflow,
        ExecutionContext $context
    ): void;
}