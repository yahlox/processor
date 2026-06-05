<?php

declare(strict_types=1);

namespace Yahlox\Domain;

use Yahlox\Exceptions\InvalidWorkflowException;

/**
 * Represents a parsed workflow graph of nodes and edges.
 *
 * @package Yahlox
 */
final class Workflow
{
    private array $nodeMap = [];
    private array $outgoingEdgesMap = [];

    /**
     * @param Node[] $nodes
     * @param Edge[] $edges
     */
    public function __construct(
        private readonly array $nodes,
        private readonly array $edges
    ) {
        foreach ($nodes as $node) {
            $this->nodeMap[$node->id()] = $node;
        }

        foreach ($edges as $edge) {
            $this->outgoingEdgesMap[$edge->source()][] = $edge;
        }
    }

    /**
     * @return Node[]
     */
    /**
     * Nodes.
     * @return array
     */
    public function nodes(): array
    {
        return $this->nodes;
    }

    /**
     * @return Edge[]
     */
    /**
     * Edges.
     * @return array
     */
    public function edges(): array
    {
        return $this->edges;
    }

    /**
     * GetNode.
     * @param string $id
     * @return ?Node
     */
    public function getNode(string $id): ?Node
    {
        return $this->nodeMap[$id] ?? null;
    }

    /**
     * GetStartNode.
     * @return Node
     */
    public function getStartNode(): Node
    {
        foreach ($this->nodes as $node) {
            if ($node->type() === 'start') {
                return $node;
            }
        }

        throw new InvalidWorkflowException(
            'Start node not found.'
        );
    }

    /**
     * NextNode.
     * @param Node $node
     * @return ?Node
     */
    public function nextNode(Node $node): ?Node
    {
        foreach ($this->outgoingEdgesMap[$node->id()] ?? [] as $edge) {
            return $this->getNode(
                $edge->target()
            );
        }

        return null;
    }

    /**
     * @return Edge[]
     */
    /**
     * GetOutgoingEdges.
     * @param Node $node
     * @return array
     */
    public function getOutgoingEdges(Node $node): array
    {
        return $this->outgoingEdgesMap[$node->id()] ?? [];
    }

    /**
     * @return string[]
     */
    /**
     * GetOutgoingTargetIds.
     * @param Node $node
     * @return array
     */
    public function getOutgoingTargetIds(Node $node): array
    {
        return array_map(
            fn (Edge $edge) => $edge->target(),
            $this->getOutgoingEdges($node)
        );
    }
}
