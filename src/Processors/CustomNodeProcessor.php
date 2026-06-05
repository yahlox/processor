<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use RuntimeException;

/**
 * Executes a custom callback node during workflow execution.
 *
 * @package Yahlox
 */
final class CustomNodeProcessor implements NodeProcessorInterface
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
        $data = $node->data();
        $callback = $data['callback'] ?? null;

        if (!is_callable($callback)) {
            throw new RuntimeException('Custom node requires a valid callable in "callback" data field');
        }

        $callback($node, $context);
    }
}
