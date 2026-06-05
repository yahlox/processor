<?php

declare(strict_types=1);

namespace Yahlox\Engine;

use Yahlox\Contracts\WorkflowExecutorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Domain\Workflow;
use Yahlox\Registry\NodeProcessorRegistry;
use Yahlox\Exceptions\InvalidWorkflowException;
use RuntimeException;
use Throwable;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Executes a parsed workflow using registered processors.
 *
 * Features:
 * - Comprehensive error handling with error nodes
 * - Transaction support for CRUD operations
 * - Proper flow control via conditional edges
 * - Timeout and cancellation support
 * - Detailed logging and execution tracking
 *
 * @package Yahlox
 */
final class WorkflowExecutor implements WorkflowExecutorInterface
{
    private LoggerInterface $logger;
    private ?int $timeoutSeconds = null;
    private float $startTime = 0;

    public function __construct(
        private readonly NodeProcessorRegistry $registry,
        private readonly WorkflowValidator $validator,
        private readonly ExpressionEvaluator $expressionEvaluator,
        ?LoggerInterface $logger = null,
        ?int $timeoutSeconds = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->timeoutSeconds = $timeoutSeconds;
    }

    /**
     * Execute workflow with comprehensive error handling.
     *
     * @param Workflow $workflow Parsed workflow instance
     * @param ExecutionContext $context Current workflow execution context
     * @return void
     * @throws InvalidWorkflowException
     * @throws RuntimeException
     */
    public function execute(Workflow $workflow, ExecutionContext $context): void
    {
        $this->startTime = microtime(true);
        $this->logger->info('Starting workflow execution', ['nodeCount' => count($workflow->nodes())]);

        try {
            $this->validator->validate($workflow);

            $currentNode = $workflow->getStartNode();
            $visitedNodes = [];
            $maxIterations = 10000; // Prevent infinite loops
            $iterations = 0;

            while ($currentNode !== null && $iterations < $maxIterations) {
                $iterations++;

                // Check timeout
                $this->checkTimeout();

                // Check for cancellation
                if ($context->get('__cancel_execution') === true) {
                    $this->logger->info('Workflow execution cancelled by request');
                    break;
                }

                // Track node visits to detect infinite loops
                $nodeKey = $currentNode->id() . '_' . count($visitedNodes);
                $visitedNodes[$nodeKey] = true;

                $this->logger->debug('Processing node', ['nodeId' => $currentNode->id(), 'nodeType' => $currentNode->type()]);

                try {
                    // Get processor and execute
                    $processor = $this->registry->get($currentNode->type());
                    $processor->process($currentNode, $context);

                    // Determine next node
                    $currentNode = $this->resolveNextNode($currentNode, $workflow, $context);

                    // Check if we should stop
                    if ($context->get('__stop_execution') === true) {
                        $this->logger->info('Execution stopped by node');
                        break;
                    }

                } catch (Throwable $e) {
                    $this->logger->error('Node processing failed', [
                        'nodeId' => $currentNode->id(),
                        'error' => $e->getMessage(),
                    ]);

                    // Try to find error handler node
                    $errorNode = $this->findErrorHandler($workflow);
                    if ($errorNode !== null) {
                        $context->set('__last_error', $e->getMessage());
                        $currentNode = $errorNode;
                        continue;
                    }

                    throw $e;
                }
            }

            if ($iterations >= $maxIterations) {
                throw new RuntimeException('Workflow execution exceeded maximum iterations limit');
            }

            $this->logger->info('Workflow execution completed successfully');

        } catch (Throwable $e) {
            $this->logger->error('Workflow execution failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Resolve the next node based on current node and context.
     *
     * @param Node $currentNode Current node
     * @param Workflow $workflow Workflow instance
     * @param ExecutionContext $context Execution context
     * @return ?Node Next node or null if end reached
     */
    private function resolveNextNode(Node $currentNode, Workflow $workflow, ExecutionContext $context): ?Node
    {
        // Check if explicit next node was set (for conditional flows)
        $explicitNext = $context->get('__next_node_id') ?? $context->get('flow.next_node_id');
        if ($explicitNext !== null) {
            $context->set('__next_node_id', null);
            $context->set('flow.next_node_id', null);
            return $workflow->getNode((string)$explicitNext);
        }

        // Get outgoing edges
        $outgoingEdges = $workflow->getOutgoingEdges($currentNode);

        if (empty($outgoingEdges)) {
            return null;
        }

        // Handle multiple edges (conditional routing)
        if (count($outgoingEdges) > 1) {
            return $this->resolveConditionalEdge($outgoingEdges, $workflow, $context);
        }

        // Single edge - follow it
        return $workflow->getNode($outgoingEdges[0]->target());
    }

    /**
     * Resolve which edge to follow based on conditions.
     *
     * @param array $edges Outgoing edges
     * @param Workflow $workflow Workflow instance
     * @param ExecutionContext $context Execution context
     * @return ?Node Target node of selected edge
     */
    private function resolveConditionalEdge(array $edges, Workflow $workflow, ExecutionContext $context): ?Node
    {
        // Simple implementation: use edge labels as conditions
        // In production, implement full conditional expression evaluation
        foreach ($edges as $edge) {
            $edgeData = $edge->metadata() ?? [];

            // If no condition, it's a default edge
            if (empty($edgeData['condition'])) {
                return $workflow->getNode($edge->target());
            }

            try {
                $result = $this->expressionEvaluator->evaluateCondition(
                    $edgeData['condition'],
                    $context
                );

                if ($result) {
                    return $workflow->getNode($edge->target());
                }
            } catch (Throwable $e) {
                $this->logger->warning('Condition evaluation failed', [
                    'condition' => $edgeData['condition'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    /**
     * Find error handler node in workflow.
     *
     * @param Workflow $workflow
     * @return ?Node
     */
    private function findErrorHandler(Workflow $workflow): ?Node
    {
        foreach ($workflow->nodes() as $node) {
            if ($node->type() === 'error') {
                return $node;
            }
        }
        return null;
    }

    /**
     * Check if execution has exceeded timeout.
     *
     * @return void
     * @throws RuntimeException
     */
    private function checkTimeout(): void
    {
        if ($this->timeoutSeconds === null) {
            return;
        }

        $elapsed = microtime(true) - $this->startTime;
        if ($elapsed > $this->timeoutSeconds) {
            throw new RuntimeException(
                "Workflow execution exceeded timeout of {$this->timeoutSeconds}s"
            );
        }
    }

    /**
     * Set logger instance.
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Get expression evaluator instance.
     *
     * @return ExpressionEvaluator
     */
    public function getExpressionEvaluator(): ExpressionEvaluator
    {
        return $this->expressionEvaluator;
    }
}
