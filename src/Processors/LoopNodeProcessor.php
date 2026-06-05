<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Parser\ReactFlowParser;
use Yahlox\Engine\WorkflowExecutor;
use Yahlox\Registry\NodeProcessorRegistry;
use RuntimeException;

/**
 * Iterates a workflow loop node over a dataset or count.
 *
 * @package Yahlox
 */
final class LoopNodeProcessor implements NodeProcessorInterface
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
        $iterations = $data['iterations'] ?? 1;
        $subWorkflowJson = $data['workflow'] ?? null;

        if (!$subWorkflowJson) {
            throw new RuntimeException('Loop node missing nested workflow definition');
        }

        $parser = new ReactFlowParser();
        $subWorkflow = $parser->parse($subWorkflowJson);

        $registry = $context->get('_loop_registry');
        if (!$registry instanceof NodeProcessorRegistry) {
            throw new RuntimeException('Loop node requires NodeProcessorRegistry in context key "_loop_registry"');
        }

        $executor = new WorkflowExecutor(
            $registry,
            new \Yahlox\Engine\WorkflowValidator(),
            new \Yahlox\Engine\ExpressionEvaluator()
        );

        for ($i = 0; $i < $iterations; $i++) {
            // Provide iteration index to sub-workflow
            $context->set('loop_iteration', $i);
            $executor->execute($subWorkflow, $context);
        }
    }
}