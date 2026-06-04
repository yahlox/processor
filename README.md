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
* Pluggable storage strategies (Eloquent, Context, extensible for MySQL/PostgreSQL/SQLite/NoSQL/Google Drive/Excel/MS Access)
* Pluggable send channels (Email, SMS, Viber, WhatsApp, Messenger, Telegram, and custom)
* Built-in support for branching, looping, delay, HTTP requests, CRUD operations, notifications, and custom callbacks
* Laravel service provider for dependency injection and container binding
* Safe placeholder substitution for `{variable}` values in expressions and payload fields

## Requirements

* PHP 8.4+
* `composer` to install dependencies

## Installation

```bash
composer install
```

## Usage

```php
use Yahlox\YahloxLibrary;
use Yahlox\Domain\ExecutionContext;

$workflow = [
    'nodes' => [
        ['id' => 'start', 'type' => 'start'],
        ['id' => 'save_todo', 'type' => 'createRecord', 'data' => [
            'model' => 'Todo',
            'storage' => 'eloquent',
            'fields' => [
                'title' => '{title}',
                'description' => '{description}',
                'due_date' => '{due_date}',
            ],
            'storeAs' => 'new_todo',
        ]],
        ['id' => 'end', 'type' => 'end'],
    ],
    'edges' => [
        ['source' => 'start', 'target' => 'save_todo'],
        ['source' => 'save_todo', 'target' => 'end'],
    ],
];

$context = new ExecutionContext();
$context->set('title', 'Write docs');
$context->set('description', 'Update README usage section');
$context->set('due_date', '2026-06-30');

$yahlox = new YahloxLibrary(
    new Yahlox\Parser\ReactFlowParser(),
    new Yahlox\Engine\WorkflowExecutor(new Yahlox\Registry\NodeProcessorRegistry(), new Yahlox\Engine\WorkflowValidator())
);

$yahlox->run($workflow, $context);

$newTodo = $context->get('new_todo');
```

* Use `data.storage` to select a named storage strategy such as `context` or `eloquent`.
* If `data.storage` is omitted, the engine chooses `eloquent` automatically when `data.model` resolves to an Eloquent model class; otherwise it falls back to `context`.

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
* `sendNotification` – sends a notification via a pluggable channel
  * `data.user_id` – recipient identifier
  * `data.title` – notification title
  * `data.body` – notification body
  * optional `data.channel` – send channel name (default: `log`), e.g. `telegram`, `email`
  * optional `data.config` – channel-specific configuration (API keys, credentials)
* `sendEmail` – sends an email via a pluggable channel
  * `data.to` – recipient email address
  * `data.subject` – email subject
  * `data.body` – email body
  * optional `data.channel` – send channel name (default: `email`)
  * optional `data.config` – channel-specific configuration (SMTP credentials)
* `sendSms` – sends an SMS via a pluggable channel
  * `data.to` – recipient phone number
  * `data.message` – SMS message text
  * optional `data.channel` – send channel name (default: `sms`), e.g. `sms`, `whatsapp`, `viber`
  * optional `data.config` – channel-specific configuration (provider API keys)

### CRUD-style record handling

* `createRecord` – writes a record via a pluggable storage strategy
  * `data.model` – model name (default: `GenericRecord`)
  * `data.fields` – payload fields with `{placeholder}` support
  * optional `data.storeAs`
  * optional `data.storage` – storage strategy name, e.g. `context`, `eloquent`
  * optional `data.config` – storage backend configuration (credentials, connection details)
  * optional `data.config.credentials_id` / `data.config.storage_credentials_id` – reference a stored `storage_channel_credentials` record
* `updateRecord` – updates a record via a storage strategy
  * `data.record_id`
  * optional `data.model`
  * `data.fields`
  * optional `data.storage`
  * optional `data.config` – storage backend configuration
  * optional `data.config.credentials_id` / `data.config.storage_credentials_id` – reference a stored credential
* `deleteRecord` – deletes a record via a storage strategy
  * `data.record_id`
  * optional `data.model`
  * optional `data.storage`
  * optional `data.config` – storage backend configuration
  * optional `data.config.credentials_id` / `data.config.storage_credentials_id` – reference a stored credential

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
            'storage' => 'eloquent',
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

## Storage Strategies

Yahlox uses pluggable storage strategies for CRUD operations. Configure the strategy with `data.storage`.

### Built-in strategies

- `context` – simulates persistence in workflow execution context (default)
- `eloquent` – persists to Eloquent models when available

### Storage Configuration

When using database storage strategies, provide connection details via `data.config`:

**Example: MySQL/MariaDB via Eloquent**

```php
['id' => 'save_record', 'type' => 'createRecord', 'data' => [
    'model' => 'User',
    'storage' => 'eloquent',
    'fields' => ['name' => '{name}', 'email' => '{email}'],
    'config' => [
        'connection' => 'mysql',
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
        'database' => env('DB_DATABASE'),
    ]
]]
```

**Example: PostgreSQL**

```php
'config' => [
    'connection' => 'pgsql',
    'host' => env('DB_HOST'),
    'port' => env('DB_PORT', 5432),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    'database' => env('DB_DATABASE'),
]
```

**Example: SQLite**

```php
'config' => [
    'connection' => 'sqlite',
    'database' => env('DB_DATABASE', 'database.sqlite'),
]
```

You can also reference a saved storage credential record instead of passing values inline:

```php
'config' => [
    'credentials_id' => 1,
]
```

If you want the workflow itself to carry the default storage credential, include `storage_credentials_id` at the top level of the workflow JSON. Yahlox will copy that value into the execution context so all `createRecord`, `updateRecord`, and `deleteRecord` nodes can inherit it when no node-specific credential is provided.

```php
$workflow = [
    'workflow_id' => '4qafk3Acm2150',
    'storage_credentials_id' => 1,
    'nodes' => [
        ['id' => 'start', 'type' => 'start'],
        ['id' => 'validate_create', 'type' => 'condition', 'data' => [
            'expression' => '{title} != "" && {desc} != "" && {due_date} != ""',
            'branchMapping' => ['true' => 'create_todo', 'false' => 'validation_failed_notify'],
        ]],
        ['id' => 'create_todo', 'type' => 'createRecord', 'data' => [
            'model' => 'Todo',
            'fields' => [
                'title' => '{title}',
                'description' => '{desc}',
                'due_date' => '{due_date}',
                'status' => 'open',
            ],
            'storeAs' => 'created_todo',
            'storage' => 'eloquent',
        ]],
        ['id' => 'created_notify', 'type' => 'sendNotification', 'data' => [
            'user_id' => '{user_id}',
            'title' => 'Todo created',
            'body' => 'Todo created successfully.',
        ]],
        ['id' => 'validation_failed_notify', 'type' => 'sendNotification', 'data' => [
            'user_id' => '{user_id}',
            'title' => 'Todo validation failed',
            'body' => 'Todo payload validation failed. Please provide title, desc, and due_date.',
        ]],
        ['id' => 'end', 'type' => 'end'],
    ],
    'edges' => [
        ['source' => 'start', 'target' => 'validate_create'],
        ['source' => 'validate_create', 'target' => 'create_todo'],
        ['source' => 'validate_create', 'target' => 'validation_failed_notify'],
        ['source' => 'create_todo', 'target' => 'created_notify'],
        ['source' => 'create_todo', 'target' => 'end'],
        ['source' => 'created_notify', 'target' => 'end'],
        ['source' => 'validation_failed_notify', 'target' => 'end'],
    ],
];
```

### Example: Create Todo Workflow Storage

The following workflow works with `createRecord` and will store a new `Todo` record if the `Todo` model exists as an Eloquent model:

```php
$workflow = [
    'nodes' => [
        ['id' => 'start', 'type' => 'start'],
        ['id' => 'validate_create', 'type' => 'condition', 'data' => [
            'expression' => '{title} != "" && {desc} != "" && {due_date} != ""',
            'branchMapping' => ['true' => 'create_todo', 'false' => 'validation_failed_notify'],
        ]],
        ['id' => 'create_todo', 'type' => 'createRecord', 'data' => [
            'model' => 'Todo',
            'fields' => [
                'title' => '{title}',
                'description' => '{desc}',
                'due_date' => '{due_date}',
                'status' => 'open',
            ],
            'storeAs' => 'created_todo',
            'storage' => 'eloquent',
        ]],
        ['id' => 'created_notify', 'type' => 'sendNotification', 'data' => [
            'user_id' => '{user_id}',
            'title' => 'Todo created',
            'body' => 'Todo created successfully.',
        ]],
        ['id' => 'validation_failed_notify', 'type' => 'sendNotification', 'data' => [
            'user_id' => '{user_id}',
            'title' => 'Todo validation failed',
            'body' => 'Todo payload validation failed. Please provide title, desc, and due_date.',
        ]],
        ['id' => 'end', 'type' => 'end'],
    ],
    'edges' => [
        ['source' => 'start', 'target' => 'validate_create'],
        ['source' => 'validate_create', 'target' => 'create_todo'],
        ['source' => 'validate_create', 'target' => 'validation_failed_notify'],
        ['source' => 'create_todo', 'target' => 'created_notify'],
        ['source' => 'create_todo', 'target' => 'end'],
        ['source' => 'created_notify', 'target' => 'end'],
        ['source' => 'validation_failed_notify', 'target' => 'end'],
    ],
];
```

This will:

- validate required workflow context values `title`, `desc`, and `due_date`
- create a new `Todo` record using `createRecord`
- store the created record under `created_todo` and `created_todo_id` in `ExecutionContext`
- send a notification after success or failure

If the `Todo` class is an Eloquent model, Yahlox automatically resolves the `eloquent` storage strategy. If you do not want database persistence, override with:

```php
'storage' => 'context'
```

To use the database-backed storage strategy, ensure your application has:

- a valid `Todo` Eloquent model
- correct database connection settings in your Laravel config
- the target database table and columns available for the `Todo` model

If you want to force the database connection in the node, include `storage` and `config`:

```php
['id' => 'create_todo', 'type' => 'createRecord', 'data' => [
    'model' => 'Todo',
    'storage' => 'eloquent',
    'fields' => [
        'title' => '{title}',
        'description' => '{desc}',
        'due_date' => '{due_date}',
        'status' => 'open',
    ],
    'storeAs' => 'created_todo',
    'config' => [
        'connection' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', 3306),
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
        'database' => env('DB_DATABASE'),
    ],
]]
```

Or reference a saved storage credential set by ID:

```php
['id' => 'create_todo', 'type' => 'createRecord', 'data' => [
    'model' => 'Todo',
    'storage' => 'eloquent',
    'fields' => [
        'title' => '{title}',
        'description' => '{desc}',
        'due_date' => '{due_date}',
        'status' => 'open',
    ],
    'storeAs' => 'created_todo',
    'config' => [
        'credentials_id' => 1,
    ],
]]
```

Yahlox does not rely on a workflow-owned storage credential. Instead, each workflow or node can reference a shared storage credential by ID using `data.config.credentials_id`.

Then run your workflow with the required context values:

```php
$context = new ExecutionContext();
$context->set('title', 'Buy coffee');
$context->set('desc', 'Morning shopping');
$context->set('due_date', '2026-06-10');
$context->set('user_id', 'user_123');

$yahlox->run($workflow['definition'], $context);
```

### Custom Storage Strategies

Create a new strategy by implementing `Yahlox\Contracts\StorageStrategyInterface`:

```php
class GoogleDriveStorageStrategy implements StorageStrategyInterface
{
    public function create(string $model, array $payload, ExecutionContext $context, array $metadata = []): array
    {
        $credentials = $metadata['config']['credentials'] ?? null;
        if (!$credentials) {
            return ['success' => false, 'error' => 'Missing Google API credentials'];
        }
        
        // Use Google Drive API to store record
        $fileId = $this->uploadToGoogleDrive($payload, $credentials);
        
        return ['success' => true, 'id' => $fileId, 'data' => $payload];
    }
    
    // Implement other required methods...
}
```

Register custom storage:

```php
$storageManager->register('google_drive', new GoogleDriveStorageStrategy());
```

## Send Channel Strategies

Yahlox uses pluggable send channels for notifications, emails, and messages. Specify the channel with `data.channel`.

### Built-in channels

- `log` – logs to workflow context (default)
- `email` – sends email messages
- `sms` – sends SMS via SMS provider
- `viber` – sends Viber messages
- `whatsapp` – sends WhatsApp messages
- `messenger` – sends Facebook Messenger messages
- `telegram` – sends Telegram messages

### Send Configuration

Provide credentials and configuration via `data.config`:

**Example: Email with SMTP**

```php
['id' => 'send_notification', 'type' => 'sendEmail', 'data' => [
    'to' => '{user_email}',
    'subject' => 'Welcome!',
    'body' => 'Hello {name}',
    'channel' => 'email',
    'config' => [
        'smtp_host' => env('MAIL_HOST'),
        'smtp_port' => env('MAIL_PORT'),
        'smtp_user' => env('MAIL_USERNAME'),
        'smtp_pass' => env('MAIL_PASSWORD'),
    ]
]]
```

**Example: SMS via Twilio**

```php
['id' => 'send_sms', 'type' => 'sendSms', 'data' => [
    'to' => '{phone}',
    'message' => 'Your code is {code}',
    'channel' => 'sms',
    'config' => [
        'provider' => 'twilio',
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from_number' => env('TWILIO_FROM_NUMBER'),
    ]
]]
```

**Example: WhatsApp via Twilio**

```php
['id' => 'send_whatsapp', 'type' => 'sendSms', 'data' => [
    'to' => '{whatsapp_number}',
    'message' => 'Your appointment: {date}',
    'channel' => 'whatsapp',
    'config' => [
        'provider' => 'twilio',
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
    ]
]]
```

**Example: Telegram**

```php
['id' => 'send_telegram', 'type' => 'sendNotification', 'data' => [
    'user_id' => '{telegram_chat_id}',
    'title' => 'Alert',
    'body' => 'Status: {status}',
    'channel' => 'telegram',
    'config' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    ]
]]
```

### Custom Send Channels

Create a new channel by implementing `Yahlox\Contracts\SendChannelStrategyInterface`:

```php
class SlackSendChannelStrategy implements SendChannelStrategyInterface
{
    public function send(array $payload, ExecutionContext $context, array $config = []): array
    {
        $webhook_url = $config['webhook_url'] ?? null;
        if (!$webhook_url) {
            return ['success' => false, 'error' => 'Missing Slack webhook URL'];
        }
        
        $message = [
            'text' => $payload['message'] ?? '',
            'channel' => $config['channel'] ?? '#general',
        ];
        
        // Send to Slack API
        $this->postToSlack($webhook_url, $message);
        
        $context->set('last_slack_sent', $payload);
        return ['success' => true, 'channel' => 'slack'];
    }
}
```

Register custom channel:

```php
$channelManager->register('slack', new SlackSendChannelStrategy());
```

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

### Publishing Migrations

Yahlox provides optional database migrations for storing workflows, execution history, and channel credentials.

Migrations are **automatically loaded** when Yahlox is installed. To optionally publish them to your Laravel application for customization:

```bash
php artisan vendor:publish --tag=yahlox-migrations
```

Yahlox also includes optional Eloquent model scaffolding for Laravel applications. To publish the package models into your app:

```bash
php artisan vendor:publish --tag=yahlox-models
```

For convenience, both migrations and models can be published together with:

```bash
php artisan vendor:publish --tag=yahlox
```

This publishes migrations for:

- **workflows** – stores workflow definitions (name, description, ReactFlow JSON, active status)
- **workflow_executions** – tracks execution history (workflow_id, status, context, error, timestamps)
- **send_channel_credentials** – stores API credentials for messaging channels (email, SMS, Viber, WhatsApp, Telegram, etc.)
- **storage_channel_credentials** – stores database connection details for storage strategies (host, port, database, credentials)

When using the published Laravel model, the package casts `connection_details` as encrypted data and hides it from model arrays/JSON.

After publishing (or if using auto-loaded migrations), run:

```bash
php artisan migrate
```

You can then load workflows and credentials from the database instead of hardcoding them:

```php
$workflow = \App\Models\Workflow::where('name', 'todo_workflow')->first();

$yahlox = app(\Yahlox\YahloxLibrary::class);
$context = new ExecutionContext();
$yahlox->run(json_decode($workflow->definition, true), $context);
```

Alternatively, to reference stored channel credentials in your workflow nodes:

```php
['id' => 'send_email', 'type' => 'sendEmail', 'data' => [
    'to' => '{email}',
    'subject' => 'Welcome',
    'body' => 'Hello {name}',
    'channel' => 'email',
    'config' => [
        'credentials_id' => 1,  // Reference stored SendChannelCredential record
    ]
]]
```

And to reference storage credentials:

```php
['id' => 'save_user', 'type' => 'createRecord', 'data' => [
    'model' => 'User',
    'storage' => 'eloquent',
    'fields' => ['name' => '{name}', 'email' => '{email}'],
    'config' => [
        'credentials_id' => 1,  // Reference stored StorageChannelCredential record
    ]
]]
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
php scripts/benchmark.php 10000 1000
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
