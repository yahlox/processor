<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Laravel\YahloxServiceProvider;
use Yahlox\Processors\ConditionNodeProcessor;
use Yahlox\Processors\EndNodeProcessor;
use Yahlox\Processors\StartNodeProcessor;
use Yahlox\YahloxLibrary;
use Yahlox\Registry\NodeProcessorRegistry;

final class TodoWorkflowTest extends TestCase
{
    private function createContainer(): Container
    {
        $container = new Container();
        $provider = new YahloxServiceProvider($container);
        $provider->register();

        return $container;
    }

    private function createTodoJson(): array
    {
        return [
            'nodes' => [
                ['id' => 'start', 'type' => 'start'],
                [
                    'id' => 'check_due',
                    'type' => 'condition',
                    'data' => [
                        'expression' => '{due_date} < {today}',
                        'branchMapping' => [
                            'true' => 'notify_overdue',
                            'false' => 'update_todo',
                        ],
                    ],
                ],
                ['id' => 'notify_overdue', 'type' => 'sendNotification'],
                ['id' => 'update_todo', 'type' => 'updateRecord'],
                ['id' => 'end', 'type' => 'end'],
            ],
            'edges' => [
                ['source' => 'start', 'target' => 'check_due'],
                ['source' => 'notify_overdue', 'target' => 'end'],
                ['source' => 'update_todo', 'target' => 'end'],
            ],
        ];
    }

    private function registerTodoProcessors(NodeProcessorRegistry $registry): void
    {
        $registry->register('start', new StartNodeProcessor());
        $registry->register('condition', new ConditionNodeProcessor());
        $registry->register('sendNotification', new NotifyOverdueNodeProcessor());
        $registry->register('updateRecord', new UpdateTodoNodeProcessor());
        $registry->register('end', new EndNodeProcessor());
    }

    public function test_overdue_todo_sends_notification(): void
    {
        $container = $this->createContainer();
        $registry = $container->make(NodeProcessorRegistry::class);
        $this->registerTodoProcessors($registry);

        $context = new ExecutionContext();
        $context->set('due_date', 1);
        $context->set('today', 2);

        $yahlox = $container->make(YahloxLibrary::class);
        $yahlox->run($this->createTodoJson(), $context);

        $this->assertTrue($context->get('start_executed'));
        $this->assertTrue($context->get('notification_sent'));
        $this->assertTrue($context->get('end_executed'));
        $this->assertTrue($context->get('condition.check_due'));
    }

    public function test_on_time_todo_updates_record(): void
    {
        $container = $this->createContainer();
        $registry = $container->make(NodeProcessorRegistry::class);
        $this->registerTodoProcessors($registry);

        $context = new ExecutionContext();
        $context->set('due_date', 5);
        $context->set('today', 2);

        $yahlox = $container->make(YahloxLibrary::class);
        $yahlox->run($this->createTodoJson(), $context);

        $this->assertTrue($context->get('start_executed'));
        $this->assertTrue($context->get('todo_updated'));
        $this->assertTrue($context->get('end_executed'));
        $this->assertFalse($context->get('condition.check_due'));
    }
}

final class NotifyOverdueNodeProcessor implements NodeProcessorInterface
{
    public function process(Node $node, ExecutionContext $context): void
    {
        $context->set('notification_sent', true);
    }
}

final class UpdateTodoNodeProcessor implements NodeProcessorInterface
{
    public function process(Node $node, ExecutionContext $context): void
    {
        $context->set('todo_updated', true);
    }
}
