<?php

declare(strict_types=1);

namespace Yahlox\Parser;

use Yahlox\Contracts\ParserInterface;
use Yahlox\Domain\Node;
use Yahlox\Domain\Edge;
use Yahlox\Domain\Workflow;

/**
 * Parses React Flow JSON into Yahlox workflow domain objects.
 *
 * @package Yahlox
 */
final class ReactFlowParser implements ParserInterface
{
/**
 * Parse workflow JSON into a Yahlox workflow object.
 *
 * @param array $payload Data payload for the record operation.
 * @return Workflow
 */
    public function parse(array $payload): Workflow
    {
        $nodes = [];
        $edges = [];

        foreach ($payload['nodes'] ?? [] as $item) {

            $nodes[] = new Node(
                id: (string)$item['id'],
                type: (string)$item['type'],
                data: $item['data'] ?? []
            );
        }

        foreach ($payload['edges'] ?? [] as $item) {

            $edges[] = new Edge(
                source: (string)$item['source'],
                target: (string)$item['target']
            );
        }

        return new Workflow(
            nodes: $nodes,
            edges: $edges
        );
    }
}