<?php

declare(strict_types=1);

namespace Yahlox\Parser;

use Yahlox\Contracts\ParserInterface;
use Yahlox\Domain\Node;
use Yahlox\Domain\Edge;
use Yahlox\Domain\Workflow;
use RuntimeException;

/**
 * Parses React Flow JSON into Yahlox workflow domain objects.
 *
 * Features:
 * - Schema validation for workflow structure
 * - Type validation for node and edge properties
 * - Support for edge metadata (conditions, labels)
 * - Strict validation options
 *
 * @package Yahlox
 */
final class ReactFlowParser implements ParserInterface
{
    private bool $strictValidation = true;

    public function __construct(bool $strictValidation = true)
    {
        $this->strictValidation = $strictValidation;
    }

    /**
     * Parse workflow JSON into a Yahlox workflow object.
     *
     * @param array $payload Data payload with nodes and edges
     * @return Workflow
     * @throws RuntimeException
     */
    public function parse(array $payload): Workflow
    {
        // Validate top-level structure
        $this->validatePayloadStructure($payload);

        $nodes = [];
        $edges = [];

        // Parse nodes
        foreach ($payload['nodes'] ?? [] as $item) {
            $this->validateNodeStructure($item);
            
            $nodes[] = new Node(
                id: (string)$item['id'],
                type: (string)$item['type'],
                data: $item['data'] ?? [],
                position: $item['position'] ?? null,
                metadata: $item['metadata'] ?? []
            );
        }

        // Parse edges
        foreach ($payload['edges'] ?? [] as $item) {
            $this->validateEdgeStructure($item);
            
            $edges[] = new Edge(
                source: (string)$item['source'],
                target: (string)$item['target'],
                metadata: $item['data'] ?? $item['metadata'] ?? []
            );
        }

        return new Workflow(
            nodes: $nodes,
            edges: $edges
        );
    }

    /**
     * Validate payload top-level structure.
     *
     * @param array $payload
     * @return void
     * @throws RuntimeException
     */
    private function validatePayloadStructure(array $payload): void
    {
        // Check required root properties
        if (!is_array($payload['nodes'] ?? null)) {
            throw new RuntimeException('Payload must contain "nodes" array');
        }

        if (!is_array($payload['edges'] ?? null)) {
            throw new RuntimeException('Payload must contain "edges" array');
        }

        // Validate nodes count
        if (count($payload['nodes']) === 0) {
            throw new RuntimeException('Workflow must contain at least one node');
        }
    }

    /**
     * Validate node structure.
     *
     * @param mixed $node
     * @return void
     * @throws RuntimeException
     */
    private function validateNodeStructure(mixed $node): void
    {
        if (!is_array($node)) {
            throw new RuntimeException('Node must be an array');
        }

        // Required properties
        if (empty($node['id'])) {
            throw new RuntimeException('Node must have an "id" property');
        }

        if (empty($node['type'])) {
            throw new RuntimeException('Node must have a "type" property');
        }

        // Type validation
        $id = (string)$node['id'];
        $type = (string)$node['type'];

        if (!$this->isValidNodeType($type)) {
            if ($this->strictValidation) {
                throw new RuntimeException("Unknown node type: {$type}");
            }
        }

        // ID validation - must be string and not contain special characters
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $id)) {
            throw new RuntimeException("Invalid node ID format: {$id}. Only alphanumeric, underscore, and dash allowed");
        }

        // Validate node data if present
        if (isset($node['data']) && !is_array($node['data'])) {
            throw new RuntimeException("Node data must be an array for node: {$id}");
        }

        // Validate position if present
        if (isset($node['position'])) {
            if (!is_array($node['position']) || !isset($node['position']['x'], $node['position']['y'])) {
                throw new RuntimeException("Node position must have x,y coordinates for node: {$id}");
            }
        }
    }

    /**
     * Validate edge structure.
     *
     * @param mixed $edge
     * @return void
     * @throws RuntimeException
     */
    private function validateEdgeStructure(mixed $edge): void
    {
        if (!is_array($edge)) {
            throw new RuntimeException('Edge must be an array');
        }

        if (empty($edge['source'])) {
            throw new RuntimeException('Edge must have a "source" property');
        }

        if (empty($edge['target'])) {
            throw new RuntimeException('Edge must have a "target" property');
        }

        $source = (string)$edge['source'];
        $target = (string)$edge['target'];

        // Validate node IDs
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $source)) {
            throw new RuntimeException("Invalid source node ID in edge: {$source}");
        }

        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $target)) {
            throw new RuntimeException("Invalid target node ID in edge: {$target}");
        }

        // Validate edge data/metadata if present
        if (isset($edge['data']) && !is_array($edge['data'])) {
            throw new RuntimeException("Edge data must be an array for edge: {$source} -> {$target}");
        }
    }

    /**
     * Check if node type is recognized.
     *
     * @param string $type
     * @return bool
     */
    private function isValidNodeType(string $type): bool
    {
        $validTypes = [
            'start', 'end', 'condition', 'switch', 'loop',
            'createRecord', 'readRecord', 'updateRecord', 'deleteRecord',
            'sendEmail', 'sendSms', 'sendNotification', 'httpRequest',
            'delay', 'error', 'custom'
        ];

        return in_array($type, $validTypes, true);
    }

    /**
     * Set strict validation mode.
     *
     * @param bool $strict
     * @return void
     */
    public function setStrictValidation(bool $strict): void
    {
        $this->strictValidation = $strict;
    }
}