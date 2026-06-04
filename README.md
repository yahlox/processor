# Yahlox Workflow Engine

A lightweight PHP workflow engine for executing ReactFlow-style JSON workflows and integrating with Laravel.

## Key features

- Parse and execute JSON workflows with nodes and edges
- Extensible node processor registry for custom logic
- Built-in support for CRUD, branching, delays, HTTP requests, and notifications
- Pluggable storage strategies, including Eloquent support
- Laravel service provider for container integration

## Installation

Install via Composer:

```bash
composer require yahlox/processor
```

If you are working from the source repository:

```bash
composer install
```

## Basic usage

```php
use Yahlox\YahloxLibrary;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Parser\ReactFlowParser;
use Yahlox\Engine\WorkflowExecutor;
use Yahlox\Registry\NodeProcessorRegistry;
use Yahlox\Engine\WorkflowValidator;

$workflow = [
    'nodes' => [
        ['id' => 'start', 'type' => 'start'],
        ['id' => 'create_todo', 'type' => 'createRecord', 'data' => [
            'model' => 'Todo',
            'storage' => 'eloquent',
            'fields' => [
                'title' => '{title}',
                'description' => '{description}',
            ],
            'storeAs' => 'todo',
        ]],
        ['id' => 'update_todo', 'type' => 'updateRecord', 'data' => [
            'model' => 'Todo',
            'storage' => 'eloquent',
            'record_id' => '{todo.id}',
            'fields' => [
                'status' => 'completed',
            ],
        ]],
        ['id' => 'delete_todo', 'type' => 'deleteRecord', 'data' => [
            'model' => 'Todo',
            'storage' => 'eloquent',
            'record_id' => '{todo.id}',
        ]],
        ['id' => 'end', 'type' => 'end'],
    ],
    'edges' => [
        ['source' => 'start', 'target' => 'create_todo'],
        ['source' => 'create_todo', 'target' => 'update_todo'],
        ['source' => 'update_todo', 'target' => 'delete_todo'],
        ['source' => 'delete_todo', 'target' => 'end'],
    ],
];

$context = new ExecutionContext();
$context->set('title', 'Write docs');
$context->set('description', 'Demonstrate CRUD example');

$yahlox = new YahloxLibrary(
    new ReactFlowParser(),
    new WorkflowExecutor(new NodeProcessorRegistry(), new WorkflowValidator())
);

$yahlox->run($workflow, $context);

$createdRecord = $context->get('todo');

// --- Read example ---
// Later in a workflow you can read the record back by id, by query, or select all.
// Use node type `readRecord` and optionally `storeAs` to capture the result.

/*
['id' => 'get_todo', 'type' => 'readRecord', 'data' => [
    'model' => 'Todo',
    'record_id' => '{todo.id}',
    'storeAs' => 'fetched_todo',
]],
*/

/*
['id' => 'get_all_todos', 'type' => 'readRecord', 'data' => [
    'model' => 'Todo',
    'storeAs' => 'all_todos',
]],
*/

/*
['id' => 'find_pending', 'type' => 'readRecord', 'data' => [
    'model' => 'Todo',
    'query' => [
        ['status', 'pending'],
    ],
    'storeAs' => 'pending_todos',
]],
*/

// After running a workflow that includes one of the above nodes, read result is available:
$fetched = $context->get('fetched_todo');
```

That is the basic flow: define a CRUD workflow, create an execution context, instantiate Yahlox, and run the workflow.
