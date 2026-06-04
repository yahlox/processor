<?php

declare(strict_types=1);

namespace Yahlox\Engine;

use Yahlox\Contracts\WorkflowExecutorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Workflow;
use Yahlox\Registry\NodeProcessorRegistry;

/**
 * Executes a parsed workflow using registered processors.
 *
 * @package Yahlox
 */
final class WorkflowExecutor implements WorkflowExecutorInterface
{
    private const FLOW_NEXT_NODE = 'flow.next_node_id';

    public function __construct(
        private readonly NodeProcessorRegistry $registry,
        private readonly WorkflowValidator $validator
    ) {
    }

/**
 * Execute.
 * @param Workflow $workflow Parsed workflow instance.
 * @param ExecutionContext $context Current workflow execution context.
 * @return void
 */
    public function execute(Workflow $workflow, ExecutionContext $context): void
    {
        $this->validator->validate($workflow);
        $currentNode = $workflow->getStartNode();

        while ($currentNode !== null) {
            $processor = $this->registry->get($currentNode->type());
            $processor->process($currentNode, $context);

            $nextId = $context->get(self::FLOW_NEXT_NODE);
            $context->set(self::FLOW_NEXT_NODE, null);

            if (is_string($nextId)) {
                $currentNode = $workflow->getNode($nextId);
            } else {
                $outgoing = $workflow->getOutgoingEdges($currentNode);
                $currentNode = !empty($outgoing)
                    ? $workflow->getNode($outgoing[0]->target())
                    : null;
            }
        }
    }
}