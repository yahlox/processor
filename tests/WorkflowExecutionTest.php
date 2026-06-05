<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Engine\ExpressionEvaluator;
use Yahlox\Engine\WorkflowExecutor;
use Yahlox\Engine\WorkflowValidator;
use Yahlox\Parser\ReactFlowParser;
use Yahlox\Registry\NodeProcessorRegistry;

use Tests\Fixtures\StartNodeProcessor;
use Tests\Fixtures\EndNodeProcessor;

final class WorkflowExecutionTest extends TestCase
{
    public function test_workflow_executes()
    {
        $json = [
            'nodes' => [
                [
                    'id' => '1',
                    'type' => 'start',
                ],
                [
                    'id' => '2',
                    'type' => 'end',
                ],
            ],
            'edges' => [
                [
                    'source' => '1',
                    'target' => '2',
                ],
            ],
        ];

        $parser = new ReactFlowParser();

        $workflow = $parser->parse(
            $json
        );

        $registry = new NodeProcessorRegistry();

        $registry->register(
            'start',
            new StartNodeProcessor()
        );

        $registry->register(
            'end',
            new EndNodeProcessor()
        );

        $executor = new WorkflowExecutor(
            $registry,
            new WorkflowValidator(),
            new ExpressionEvaluator()
        );

        $context = new ExecutionContext();

        $executor->execute(
            $workflow,
            $context
        );

        $this->assertTrue(
            $context->get('start_executed')
        );

        $this->assertTrue(
            $context->get('end_executed')
        );
    }
}