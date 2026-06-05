<?php

declare(strict_types=1);

namespace Yahlox\Engine;

use Yahlox\Domain\Workflow;
use Yahlox\Exceptions\InvalidWorkflowException;

/**
 * Validates workflow structure with comprehensive checks including:
 * - Start/end node validation
 * - Cycle detection using DFS
 * - Graph connectivity
 * - Node type validation
 * - Edge validation
 *
 * @package Yahlox
 */
final class WorkflowValidator
{
    private array $visited = [];
    private array $recursionStack = [];

    /**
     * Validate workflow structure comprehensively.
     *
     * @param Workflow $workflow Workflow to validate
     * @return void
     * @throws InvalidWorkflowException
     */
    public function validate(Workflow $workflow): void
    {
        $this->validateStartNodes($workflow);
        $this->validateEndNodes($workflow);
        $this->validateEdges($workflow);
        $this->validateNoCycles($workflow);
        $this->validateConnectivity($workflow);
        $this->validateNodeTypes($workflow);
    }

    /**
     * Validate that exactly one start node exists.
     *
     * @param Workflow $workflow
     * @return void
     * @throws InvalidWorkflowException
     */
    private function validateStartNodes(Workflow $workflow): void
    {
        $startCount = 0;
        $startNodeIds = [];

        foreach ($workflow->nodes() as $node) {
            if ($node->type() === 'start') {
                $startCount++;
                $startNodeIds[] = $node->id();
            }
        }

        if ($startCount === 0) {
            throw new InvalidWorkflowException(
                'Workflow must contain exactly one start node.'
            );
        }

        if ($startCount > 1) {
            throw new InvalidWorkflowException(
                "Workflow contains multiple start nodes: " . implode(', ', $startNodeIds)
            );
        }
    }

    /**
     * Validate that at least one end node exists.
     *
     * @param Workflow $workflow
     * @return void
     * @throws InvalidWorkflowException
     */
    private function validateEndNodes(Workflow $workflow): void
    {
        $endCount = 0;

        foreach ($workflow->nodes() as $node) {
            if ($node->type() === 'end') {
                $endCount++;
            }
        }

        if ($endCount === 0) {
            throw new InvalidWorkflowException(
                'Workflow must contain at least one end node.'
            );
        }
    }

    /**
     * Validate all edges reference existing nodes.
     *
     * @param Workflow $workflow
     * @return void
     * @throws InvalidWorkflowException
     */
    private function validateEdges(Workflow $workflow): void
    {
        $nodeIds = array_map(fn ($n) => $n->id(), $workflow->nodes());
        $nodeIdSet = array_flip($nodeIds);

        foreach ($workflow->edges() as $edge) {
            if (!isset($nodeIdSet[$edge->source()])) {
                throw new InvalidWorkflowException(
                    "Edge references non-existent source node: {$edge->source()}"
                );
            }

            if (!isset($nodeIdSet[$edge->target()])) {
                throw new InvalidWorkflowException(
                    "Edge references non-existent target node: {$edge->target()}"
                );
            }

            if ($edge->source() === $edge->target()) {
                throw new InvalidWorkflowException(
                    "Self-loop detected on node: {$edge->source()}"
                );
            }
        }
    }

    /**
     * Detect cycles using Depth-First Search (DFS).
     *
     * @param Workflow $workflow
     * @return void
     * @throws InvalidWorkflowException
     */
    private function validateNoCycles(Workflow $workflow): void
    {
        $this->visited = [];
        $this->recursionStack = [];

        foreach ($workflow->nodes() as $node) {
            if (!isset($this->visited[$node->id()])) {
                if ($this->hasCycleDFS($node->id(), $workflow)) {
                    throw new InvalidWorkflowException(
                        'Workflow contains a cycle. Workflows must be acyclic (DAG).'
                    );
                }
            }
        }
    }

    /**
     * DFS helper to detect cycles.
     *
     * @param string $nodeId Current node ID
     * @param Workflow $workflow Workflow instance
     * @return bool True if cycle detected
     */
    private function hasCycleDFS(string $nodeId, Workflow $workflow): bool
    {
        $this->visited[$nodeId] = true;
        $this->recursionStack[$nodeId] = true;

        $outgoingEdges = $workflow->getOutgoingEdges($workflow->getNode($nodeId));

        foreach ($outgoingEdges as $edge) {
            $targetId = $edge->target();

            if (!isset($this->visited[$targetId])) {
                if ($this->hasCycleDFS($targetId, $workflow)) {
                    return true;
                }
            } elseif (isset($this->recursionStack[$targetId])) {
                return true;
            }
        }

        unset($this->recursionStack[$nodeId]);
        return false;
    }

    /**
     * Validate workflow is fully connected (no isolated nodes).
     *
     * @param Workflow $workflow
     * @return void
     * @throws InvalidWorkflowException
     */
    private function validateConnectivity(Workflow $workflow): void
    {
        $startNode = $workflow->getStartNode();
        $reachable = [];
        $queue = [$startNode->id()];
        $visited = [];

        // BFS from start node
        while (!empty($queue)) {
            $current = array_shift($queue);
            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;
            $reachable[$current] = true;

            $currentNode = $workflow->getNode($current);
            foreach ($workflow->getOutgoingEdges($currentNode) as $edge) {
                if (!isset($visited[$edge->target()])) {
                    $queue[] = $edge->target();
                }
            }
        }

        // Check all nodes are reachable
        foreach ($workflow->nodes() as $node) {
            if ($node->type() === 'start') {
                continue; // Start node always reachable
            }

            if (!isset($reachable[$node->id()])) {
                throw new InvalidWorkflowException(
                    "Node '{$node->id()}' is not reachable from the start node."
                );
            }
        }

        // Check all reachable nodes can reach an end node
        foreach ($reachable as $nodeId => $_) {
            if (!$this->canReachEndNode((string)$nodeId, $workflow)) {
                throw new InvalidWorkflowException(
                    "Node '{$nodeId}' cannot reach any end node."
                );
            }
        }
    }

    /**
     * Check if a node can reach an end node.
     *
     * @param string $nodeId Node ID
     * @param Workflow $workflow Workflow instance
     * @return bool
     */
    private function canReachEndNode(string $nodeId, Workflow $workflow): bool
    {
        $node = $workflow->getNode($nodeId);

        if ($node->type() === 'end') {
            return true;
        }

        $visited = [];
        $queue = [$nodeId];

        while (!empty($queue)) {
            $current = array_shift($queue);
            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;

            $currentNode = $workflow->getNode($current);
            if ($currentNode->type() === 'end') {
                return true;
            }

            foreach ($workflow->getOutgoingEdges($currentNode) as $edge) {
                if (!isset($visited[$edge->target()])) {
                    $queue[] = $edge->target();
                }
            }
        }

        return false;
    }

    /**
     * Validate all node types are recognized.
     *
     * @param Workflow $workflow
     * @return void
     * @throws InvalidWorkflowException
     */
    private function validateNodeTypes(Workflow $workflow): void
    {
        $validTypes = [
            'start', 'end', 'condition', 'switch', 'loop',
            'createRecord', 'readRecord', 'updateRecord', 'deleteRecord',
            'sendEmail', 'sendSms', 'sendNotification', 'httpRequest',
            'delay', 'custom'
        ];

        foreach ($workflow->nodes() as $node) {
            if (!in_array($node->type(), $validTypes, true)) {
                throw new InvalidWorkflowException(
                    "Unknown node type: {$node->type()}. Valid types: " .
                    implode(', ', $validTypes)
                );
            }
        }
    }
}
