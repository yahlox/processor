<?php

declare(strict_types=1);

namespace Tests\Processors;

use PHPUnit\Framework\TestCase;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Processors\LoopNodeProcessor;
use Yahlox\Registry\NodeProcessorRegistry;
use Yahlox\Processors\StartNodeProcessor;
use Yahlox\Processors\EndNodeProcessor;

final class LoopNodeProcessorTest extends TestCase
{
    public function testLoopIterates(): void
    {
        $subWorkflowJson = [
            'nodes' => [
                ['id' => 'start', 'type' => 'start'],
                ['id' => 'end', 'type' => 'end']
            ],
            'edges' => [['source' => 'start', 'target' => 'end']]
        ];

        $context = new ExecutionContext();
        $context->set('counter', 0);
        $context->set('_loop_registry', $this->createTestRegistry());

        $node = new Node('loop1', 'loop', [
            'iterations' => 3,
            'workflow' => $subWorkflowJson
        ]);

        // We need to inject a custom start processor that increments counter
        $registry = $context->get('_loop_registry');
        $registry->register('start', new class implements \Yahlox\Contracts\NodeProcessorInterface {
            public function process(\Yahlox\Domain\Node $node, \Yahlox\Domain\ExecutionContext $context): void
            {
                $counter = $context->get('counter') + 1;
                $context->set('counter', $counter);
            }
        });
        $registry->register('end', new EndNodeProcessor());

        (new LoopNodeProcessor())->process($node, $context);
        $this->assertSame(3, $context->get('counter'));
    }

    private function createTestRegistry(): NodeProcessorRegistry
    {
        return new NodeProcessorRegistry();
    }
}