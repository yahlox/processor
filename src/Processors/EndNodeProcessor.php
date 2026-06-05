<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;

/**
 * Signals the end of a workflow execution path.
 *
 * @package Yahlox
 */
final class EndNodeProcessor implements NodeProcessorInterface
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
        $context->set('end_executed', true);
    }
}
