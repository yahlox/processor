<?php

declare(strict_types=1);

namespace Tests\Processors;

use PHPUnit\Framework\TestCase;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Processors\ConditionNodeProcessor;

final class ConditionNodeProcessorTest extends TestCase
{
    public function testConditionTrueBranch(): void
    {
        $context = new ExecutionContext();
        $context->set('age', 25);

        $node = new Node('cond1', 'condition', [
            'expression' => '{age} > 18',
            'branchMapping' => ['true' => 'adult_node', 'false' => 'child_node']
        ]);

        $processor = new ConditionNodeProcessor();
        $processor->process($node, $context);

        $this->assertSame('adult_node', $context->get('flow.next_node_id'));
        $this->assertTrue($context->get('condition.cond1'));
    }

    public function testConditionFalseBranch(): void
    {
        $context = new ExecutionContext();
        $context->set('age', 16);

        $node = new Node('cond1', 'condition', [
            'expression' => '{age} > 18',
            'branchMapping' => ['true' => 'adult_node', 'false' => 'child_node']
        ]);

        $processor = new ConditionNodeProcessor();
        $processor->process($node, $context);

        $this->assertSame('child_node', $context->get('flow.next_node_id'));
        $this->assertFalse($context->get('condition.cond1'));
    }

    public function testConditionSupportsSqlIn(): void
    {
        $context = new ExecutionContext();
        $context->set('status', 'open');

        $node = new Node('cond1', 'condition', [
            'expression' => '{status} IN ("open", "closed")',
            'branchMapping' => ['true' => 'allowed_node', 'false' => 'blocked_node']
        ]);

        $processor = new ConditionNodeProcessor();
        $processor->process($node, $context);

        $this->assertSame('allowed_node', $context->get('flow.next_node_id'));
        $this->assertTrue($context->get('condition.cond1'));
    }

    public function testConditionSupportsSqlLike(): void
    {
        $context = new ExecutionContext();
        $context->set('name', 'Task 123');

        $node = new Node('cond1', 'condition', [
            'expression' => '{name} LIKE "Task %"',
            'branchMapping' => ['true' => 'match_node', 'false' => 'nomatch_node']
        ]);

        $processor = new ConditionNodeProcessor();
        $processor->process($node, $context);

        $this->assertSame('match_node', $context->get('flow.next_node_id'));
        $this->assertTrue($context->get('condition.cond1'));
    }
}