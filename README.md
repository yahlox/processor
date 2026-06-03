# Yahlox Workflow Engine

A PHP 8.4 workflow engine that executes ReactFlow-style JSON workflows as backend business logic.

## Overview

Yahlox converts JSON-defined workflows into domain objects and executes them using node processors.
It is designed for business process automation, backend orchestration, and integration into Laravel applications.

## Features

* ReactFlow JSON parser with `nodes` / `edges`
* Workflow validation enforcing exactly one `start` node
* Executable workflow graph with `ExecutionContext` state propagation
* Extensible `NodeProcessorRegistry` for custom node types
* Built-in support for branching, looping, delay, HTTP requests, CRUD operations, notifications, email, SMS, and custom callbacks
* Laravel service provider for dependency injection and container binding
* Safe placeholder substitution for `{variable}` values in expressions and payload fields

## Requirements

* PHP 8.4+
* `composer` to install dependencies

## Installation

```bash
composer install
```

## Run Tests

```bash
vendor/bin/phpunit
```

Expected output:

```text
PHPUnit 13.x

OK (20 tests, 42 assertions)
```

## Workflow JSON Structure

A Yahlox workflow is a JSON payload with two top-level arrays:

* `nodes` – workflow steps, each with `id`, `type`, and optional `data`
* `edges` – graph links, each with `source` and `target`

Example:

```php
$json = [
    'nodes' => [
        ['id' => 'start', 'type' => 'start'],
        ['id' => 'end', 'type' => 'end'],
    ],
    'edges' => [
        ['source' => 'start', 'target' => 'end'],
    ],
];
```

## How It Works

1. `Yahlox\Parser\ReactFlowParser` converts JSON into `Workflow`, `Node`, and `Edge` objects.
2. `WorkflowValidator` verifies the workflow contains exactly one `start` node.
3. `WorkflowExecutor` traverses the nodes and dispatches each node to the registered processor.
4. `ExecutionContext` carries values between node executions.

## Built-in Node Types

### Flow control

* `start` – entry point for workflow execution
* `end` – stops execution
* `condition` – evaluates an expression and routes to `true` / `false`
  * `data.expression` – expression using `{placeholders}`
  * `data.branchMapping` – map `true` / `false` to next node IDs
* `switch` – evaluates an expression and routes to a matching case
  * `data.expression` – value expression
  * `data.cases` – map values to target node IDs, optional `default`
* `loop` – executes a nested workflow multiple times
  * `data.iterations` – number of loop iterations
  * `data.workflow` – nested ReactFlow JSON payload
* `delay` – pauses execution for a number of seconds/milliseconds
  * `data.seconds`
  * `data.milliseconds`

### Integration and I/O

* `httpRequest` – performs an HTTP request with `curl`
  * `data.url`, `data.method`, `data.headers`, `data.body`
  * optional `data.storeResponseAs` to save the response in context
* `sendNotification` – records a notification payload
  * `data.user_id`, `data.title`, `data.body`
* `sendEmail` – records an email payload
  * `data.to`, `data.subject`, `data.body`
* `sendSms` – records an SMS payload
  * `data.to`, `data.message`

### CRUD-style record handling

* `createRecord` – creates a simulated record and stores it in context
  * `data.model` – model name (default: `GenericRecord`)
  * `data.fields` – payload fields with `{placeholder}` support
  * optional `data.storeAs`
* `updateRecord` – updates a simulated record in context
  * `data.record_id`
  * `data.fields`
* `deleteRecord` – deletes a simulated record in context
  * `data.record_id`

### Extension

* `custom` – runs a PHP callable defined in `data.callback`

## Expression Support

Condition nodes support safe expressions with variable placeholders and SQL-style operators.

### Supported operators

* Comparison: `==`, `===`, `!=`, `!==`, `<`, `>`, `<=`, `>=`
* Logical: `&&`, `||`, `!`, `and`, `or`, `xor`
* SQL-style: `IN`, `NOT IN`, `LIKE`, `NOT LIKE`, `BETWEEN`, `IS NULL`, `IS NOT NULL`

### Placeholder syntax

Use `{variable}` to inject context values into expressions and processor payloads.

Example:

```php
[ 'expression' => '{due_date} < {today}' ]
```

## Example: Todo CRUD Workflow

```php
$json = [
    'nodes' => [
        ['id' => 'start', 'type' => 'start'],

        ['id' => 'action_is_create', 'type' => 'condition', 'data' => [
            'expression' => '{action} == "create"',
            'branchMapping' => ['true' => 'validate_create', 'false' => 'action_is_update'],
        ]],

        ['id' => 'validate_create', 'type' => 'condition', 'data' => [
            'expression' => '{title} != "" && {description} != "" && {due_date} >= {today}',
            'branchMapping' => ['true' => 'create_todo', 'false' => 'validation_failed_notify'],
        ]],

        ['id' => 'create_todo', 'type' => 'createRecord', 'data' => [
            'model' => 'Todo',
            'fields' => [
                'title' => '{title}',
                'description' => '{description}',
                'due_date' => '{due_date}',
                'status' => 'open',
            ],
            'storeAs' => 'new_todo',
        ]],

        ['id' => 'create_success_notify', 'type' => 'sendNotification', 'data' => [
            'user_id' => '{owner_id}',
            'title' => 'Todo created',
            'body' => 'Your todo "{title}" was created.',
        ]],

        ['id' => 'update_todo', 'type' => 'updateRecord', 'data' => [
            'record_id' => '{todo_id}',
            'fields' => [
                'title' => '{title}',
                'description' => '{description}',
                'due_date' => '{due_date}',
            ],
        ]],

        ['id' => 'complete_todo', 'type' => 'updateRecord', 'data' => [
            'record_id' => '{todo_id}',
            'fields' => [
                'status' => 'completed',
                'completed_at' => '{today}',
            ],
        ]],

        ['id' => 'delete_todo', 'type' => 'deleteRecord', 'data' => [
            'record_id' => '{todo_id}',
        ]],

        ['id' => 'validation_failed_notify', 'type' => 'sendNotification', 'data' => [
            'user_id' => '{owner_id}',
            'title' => 'Validation failed',
            'body' => 'Please fix required fields for the todo.',
        ]],

        ['id' => 'not_found_notify', 'type' => 'sendNotification', 'data' => [
            'user_id' => '{owner_id}',
            'title' => 'Todo not found',
            'body' => 'Could not locate todo with id {todo_id}.',
        ]],

        ['id' => 'end', 'type' => 'end'],
    ],
    'edges' => [
        ['source' => 'start', 'target' => 'action_is_create'],
        ['source' => 'action_is_create', 'target' => 'validate_create'],
        ['source' => 'action_is_create', 'target' => 'action_is_update'],
        ['source' => 'validate_create', 'target' => 'create_todo'],
        ['source' => 'validate_create', 'target' => 'validation_failed_notify'],
        ['source' => 'create_todo', 'target' => 'create_success_notify'],
        ['source' => 'create_success_notify', 'target' => 'end'],
        ['source' => 'not_found_notify', 'target' => 'end'],
        ['source' => 'validation_failed_notify', 'target' => 'end'],
    ],
];
```

This example demonstrates:

* action routing
* data validation
* create/update/delete flow branches
* notifications for success and failure
* terminal `end` node

## Laravel Integration

Yahlox includes a Laravel service provider at `src/Laravel/YahloxServiceProvider.php`.
It binds the core engine services and enables `YahloxLibrary` injection in your application.

Register custom processors by binding them to `NodeProcessorRegistry` in your service provider.

Example:

```php
use Yahlox\Registry\NodeProcessorRegistry;
use App\Workflow\Processors\YourCustomProcessor;

public function boot(NodeProcessorRegistry $registry): void
{
    $registry->register('your_custom_type', new YourCustomProcessor());
}
```

## Custom Nodes

The `custom` node type allows you to execute a PHP callback in the workflow:

```php
['id' => 'custom', 'type' => 'custom', 'data' => ['callback' => function ($node, $context) {
    // custom processing
}]]
```

## Status

* Core workflow engine implemented
* ReactFlow parser built
* Workflow validator active
* Built-in node processors available
* Laravel service provider included
* Full PHPUnit suite passing

* Executor
* Unit Tests
* Condition Node
* Switch Node
* CRUD Nodes
* Email/SMS/Notification Nodes
* HTTP Request Node
* Loop Node
* Delay Node
* Custom Node
* Todo workflow coverage
* Laravel Package Integration

Next Phase:

* Package documentation and publishing

## Benchmarking

A small benchmark script is provided at `scripts/benchmark.php` to measure parser and full run throughput. It is a synthetic single-process benchmark and reports operations per second for:

- parse-only (JSON -> Workflow)
- parse + execute (full `YahloxLibrary::run`)

Run with defaults (20k iterations):

```bash
php scripts/benchmark.php 20000 1000
```

Or via Composer script:

```bash
composer run-script benchmark
```

Sample output from the repository environment:

```
Yahlox benchmark starting — iterations=20000 warmup=1000
Parse-only: 1941178.32 ops/sec (total 0.0103s)
Parse+Execute: 791519.99 ops/sec (total 0.0253s)
Benchmark complete.
```

Note: These numbers are synthetic and highly dependent on the host hardware, PHP runtime, and opcode caching. Use the script to benchmark your target environment and tune workflow complexity and node processors for real-world performance.
