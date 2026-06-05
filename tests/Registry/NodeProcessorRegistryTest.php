<?php

declare(strict_types=1);

namespace Tests\Registry;

use PHPUnit\Framework\TestCase;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Engine\ExpressionEvaluator;
use Yahlox\Engine\WorkflowExecutor;
use Yahlox\Engine\WorkflowValidator;
use Yahlox\Parser\ReactFlowParser;
use Yahlox\Registry\NodeProcessorRegistry;

final class NodeProcessorRegistryTest extends TestCase
{
    public function test_processors_are_auto_registered_when_workflow_contains_types(): void
    {
        $json = [
            'nodes' => [
                ['id' => 'start', 'type' => 'start'],
                ['id' => 'end', 'type' => 'end'],
            ],
            'edges' => [
                ['source' => 'start', 'target' => 'end'],
            ],
        ];

        $workflow = (new ReactFlowParser())->parse($json);
        $registry = new NodeProcessorRegistry();
        $executor = new WorkflowExecutor($registry, new WorkflowValidator(), new ExpressionEvaluator());
        $context = new ExecutionContext();

        $executor->execute($workflow, $context);

        $this->assertTrue($context->get('start_executed'));
        $this->assertTrue($context->get('end_executed'));
    }
}
