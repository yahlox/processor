<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;

/**
 * Marks the start of workflow execution and continues to the next node.
 *
 * @package Yahlox
 */
final class StartNodeProcessor implements NodeProcessorInterface
{
    /**
     * Execute processor logic for the workflow node and update the execution context.
     *
     * @param Node $node Workflow node to process.
     * @param ExecutionContext $context Current workflow execution context.
     * @return void
     */
    public function process(Node $node, ExecutionContext $context): void
    {
        $context->set('start_executed', true);
    }
}
