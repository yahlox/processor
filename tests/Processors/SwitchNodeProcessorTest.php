<?php

declare(strict_types=1);

namespace Tests\Processors;

use PHPUnit\Framework\TestCase;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Processors\SwitchNodeProcessor;

final class SwitchNodeProcessorTest extends TestCase
{
    public function testSwitchMatchesCase(): void
    {
        $context = new ExecutionContext();
        $context->set('status', 'active');

        $node = new Node('sw1', 'switch', [
            'expression' => '{status}',
            'cases' => [
                'active' => 'active_handler',
                'inactive' => 'inactive_handler',
                'default' => 'default_handler'
            ]
        ]);

        $processor = new SwitchNodeProcessor();
        $processor->process($node, $context);

        $this->assertSame('active_handler', $context->get('flow.next_node_id'));
    }

    public function testSwitchUsesDefault(): void
    {
        $context = new ExecutionContext();
        $context->set('status', 'unknown');

        $node = new Node('sw1', 'switch', [
            'expression' => '{status}',
            'cases' => ['active' => 'active_handler', 'default' => 'default_handler']
        ]);

        $processor = new SwitchNodeProcessor();
        $processor->process($node, $context);

        $this->assertSame('default_handler', $context->get('flow.next_node_id'));
    }
}