# Yahlox Processor - Comprehensive Documentation

## Table of Contents
1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Quick Start](#quick-start)
4. [Workflow Structure](#workflow-structure)
5. [Node Types](#node-types)
6. [Configuration Guide](#configuration-guide)
7. [Security Features](#security-features)
8. [Error Handling](#error-handling)
9. [Advanced Features](#advanced-features)
10. [Troubleshooting](#troubleshooting)

## Introduction

Yahlox is a powerful PHP workflow engine that parses ReactFlow JSON diagrams and executes them as dynamic workflows. It's designed to be secure, reliable, and easy to integrate into Laravel applications.

### Key Features
- ✅ ReactFlow JSON parsing with schema validation
- ✅ Comprehensive workflow validation (cycle detection, connectivity checks)
- ✅ Safe expression evaluation for variable substitution
- ✅ 15+ pre-built node processors (email, SMS, HTTP, CRUD, etc.)
- ✅ Error handling and recovery mechanisms
- ✅ Rate limiting and retry policies
- ✅ Timeout protection
- ✅ Full transaction support for database operations
- ✅ Comprehensive logging and observability
- ✅ PHP 8.0+ compatibility with multiple Laravel versions

### PHP Compatibility
Requires PHP 8.0+ and works with Laravel 10, 11, 12, and 13.

## Installation

```bash
composer require yahlox/processor
```

## Quick Start

### Basic Usage

```php
use Yahlox\Parser\ReactFlowParser;
use Yahlox\Engine\WorkflowValidator;
use Yahlox\Engine\WorkflowExecutor;
use Yahlox\Engine\ExpressionEvaluator;
use Yahlox\Registry\NodeProcessorRegistry;
use Yahlox\Domain\ExecutionContext;

// 1. Parse workflow JSON from ReactFlow
$parser = new ReactFlowParser(strictValidation: true);
$workflow = $parser->parse($jsonPayload);

// 2. Validate workflow
$validator = new WorkflowValidator();
$validator->validate($workflow);

// 3. Set up executor with dependencies
$registry = new NodeProcessorRegistry();
$expressionEvaluator = new ExpressionEvaluator();
$executor = new WorkflowExecutor(
    registry: $registry,
    validator: $validator,
    expressionEvaluator: $expressionEvaluator,
    timeoutSeconds: 300
);

// 4. Create execution context with initial variables
$context = new ExecutionContext();
$context->set('user_email', 'user@example.com');
$context->set('order_id', 12345);

// 5. Execute workflow
try {
    $executor->execute($workflow, $context);
    echo "Workflow completed successfully!";
} catch (Exception $e) {
    echo "Workflow failed: " . $e->getMessage();
}
```

## Workflow Structure

A workflow consists of **nodes** (operations) and **edges** (connections).

### JSON Schema

```json
{
  "nodes": [
    {
      "id": "node-1",
      "type": "start",
      "data": {},
      "position": { "x": 100, "y": 100 },
      "metadata": {}
    }
  ],
  "edges": [
    {
      "source": "node-1",
      "target": "node-2",
      "data": {
        "condition": "{status} == 'active'"
      }
    }
  ]
}
```

### Valid Node IDs
- Only alphanumeric characters, underscore (_), and dash (-)
- Example: `node-1`, `email_task`, `check_status123`

### Edge Metadata
Edges can include optional metadata for conditional routing:
```json
{
  "source": "condition-node",
  "target": "email-node",
  "data": {
    "condition": "{result} == 'approved'",
    "label": "Approved Path"
  }
}
```

## Node Types

### 1. Start Node
Entry point for the workflow. Exactly one required.
```json
{
  "id": "start",
  "type": "start",
  "data": {}
}
```

### 2. End Node
Workflow completion marker. At least one required.
```json
{
  "id": "end",
  "type": "end",
  "data": {}
}
```

### 3. Condition Node
Branching based on a condition.
```json
{
  "id": "check",
  "type": "condition",
  "data": {
    "expression": "{amount} > 1000"
  }
}
```

### 4. Switch Node
Multiple condition branches.
```json
{
  "id": "status-check",
  "type": "switch",
  "data": {
    "expression": "{status}",
    "cases": {
      "pending": "node-2",
      "approved": "node-3",
      "rejected": "node-4"
    }
  }
}
```

### 5. Loop Node
Iterate over a collection.
```json
{
  "id": "loop",
  "type": "loop",
  "data": {
    "collection": "{items}",
    "itemVariable": "current_item",
    "maxIterations": 100
  }
}
```

### 6. Create Record Node
Insert a new record in the database.
```json
{
  "id": "create-user",
  "type": "createRecord",
  "data": {
    "model": "User",
    "fields": {
      "name": "{user_name}",
      "email": "{user_email}",
      "status": "active"
    },
    "storeAs": "created_user",
    "config": {
      "storage": "eloquent"
    }
  }
}
```

### 7. Read Record Node
Fetch existing records.
```json
{
  "id": "get-user",
  "type": "readRecord",
  "data": {
    "model": "User",
    "query": {
      "id": "{user_id}"
    },
    "storeAs": "user_data",
    "config": {
      "storage": "eloquent"
    }
  }
}
```

### 8. Update Record Node
Modify existing records.
```json
{
  "id": "update-user",
  "type": "updateRecord",
  "data": {
    "model": "User",
    "id": "{user_id}",
    "fields": {
      "status": "verified",
      "updated_at": "now"
    },
    "config": {
      "storage": "eloquent"
    }
  }
}
```

### 9. Delete Record Node
Remove records from database.
```json
{
  "id": "delete-user",
  "type": "deleteRecord",
  "data": {
    "model": "User",
    "id": "{user_id}",
    "config": {
      "storage": "eloquent"
    }
  }
}
```

### 10. Send Email Node
Send email messages.
```json
{
  "id": "send-email",
  "type": "sendEmail",
  "data": {
    "to": "{user_email}",
    "subject": "Order Confirmation",
    "body": "Your order #{order_id} has been received.",
    "channel": "email",
    "htmlContent": false,
    "validateEmail": true,
    "config": {
      "from": "noreply@example.com"
    }
  }
}
```

### 11. Send SMS Node
Send SMS messages.
```json
{
  "id": "send-sms",
  "type": "sendSms",
  "data": {
    "to": "{phone_number}",
    "message": "Your verification code is {code}",
    "channel": "sms",
    "config": {}
  }
}
```

### 12. Send Notification Node
Send in-app notifications.
```json
{
  "id": "notify",
  "type": "sendNotification",
  "data": {
    "user_id": "{user_id}",
    "title": "Order Status",
    "message": "Your order is being processed",
    "channel": "notification"
  }
}
```

### 13. HTTP Request Node
Make HTTP requests to external APIs.
```json
{
  "id": "api-call",
  "type": "httpRequest",
  "data": {
    "url": "https://api.example.com/data/{id}",
    "method": "POST",
    "headers": {
      "Authorization": "Bearer {api_token}",
      "Content-Type": "application/json"
    },
    "body": "{\"user_id\": {user_id}, \"action\": \"process\"}",
    "timeout": 30,
    "connectTimeout": 10,
    "validateUrl": true,
    "expectStatusCodes": [200, 201],
    "storeResponseAs": "api_response"
  }
}
```

### 14. Delay Node
Pause execution for a specified duration.
```json
{
  "id": "wait",
  "type": "delay",
  "data": {
    "seconds": 5,
    "retryOn": ["timeout", "rate_limit"]
  }
}
```

### 15. Custom Node
Execute custom logic via registered handlers.
```json
{
  "id": "custom",
  "type": "custom",
  "data": {
    "handler": "MyCustomHandler",
    "params": {
      "key": "value"
    }
  }
}
```

### 16. Error Node
Handle and log workflow errors.
```json
{
  "id": "error-handler",
  "type": "error",
  "data": {
    "message": "Workflow error occurred",
    "log": true,
    "stopExecution": true,
    "storeAs": "workflow_error"
  }
}
```

## Configuration Guide

### Workflow Executor Configuration

```php
use Yahlox\Engine\WorkflowExecutor;
use Yahlox\Utils\RetryPolicy;
use Yahlox\Utils\RateLimiter;
use Psr\Log\LoggerInterface;

// Create with custom configuration
$executor = new WorkflowExecutor(
    registry: $registry,
    validator: $validator,
    expressionEvaluator: $evaluator,
    logger: $yourLogger,
    timeoutSeconds: 300  // 5 minutes max execution time
);

// Add logging
$executor->setLogger($logger);
```

### HTTP Node Configuration

```json
{
  "type": "httpRequest",
  "data": {
    "url": "https://api.example.com/endpoint",
    "method": "POST",
    "timeout": 30,
    "connectTimeout": 10,
    "validateUrl": true,
    "expectStatusCodes": [200, 201, 204],
    "storeResponseAs": "response"
  }
}
```

### Rate Limiting Configuration

```php
use Yahlox\Utils\RateLimiter;

$limiter = new RateLimiter();

// Allow 10 emails per minute per workflow
if ($limiter->isAllowed('email_sends', 10, 60)) {
    // Send email
}

// Check remaining
$remaining = $limiter->getRemaining('email_sends', 10);
```

### Retry Policy Configuration

```php
use Yahlox\Utils\RetryPolicy;

$retryPolicy = new RetryPolicy(
    maxAttempts: 3,
    initialDelayMs: 100,
    backoffMultiplier: 2.0,
    maxDelayMs: 30000
);

$result = $retryPolicy->execute(
    operation: function() { /* ... */ },
    onRetry: function($attempt, $delay) {
        echo "Retrying attempt $attempt after {$delay}ms";
    }
);
```

## Security Features

### Input Sanitization

```php
use Yahlox\Utils\InputSanitizer;

// Sanitize different types
$email = InputSanitizer::sanitize('user@example.com', 'email');
$url = InputSanitizer::sanitize('https://example.com', 'url');
$number = InputSanitizer::sanitize('123.45', 'number');
$boolean = InputSanitizer::sanitize('true', 'boolean');
```

### Safe Expression Evaluation

The `ExpressionEvaluator` safely evaluates expressions without using `eval()` on untrusted input:

```php
use Yahlox\Engine\ExpressionEvaluator;

$evaluator = new ExpressionEvaluator();

// Variable substitution
$result = $evaluator->evaluate(
    "Hello {name}, you have {count} items",
    $context
);

// Conditional evaluation
$approved = $evaluator->evaluateCondition(
    "{amount} > 1000 && {status} == 'active'",
    $context
);
```

### Workflow Validation

All workflows are validated before execution:

```php
- ✅ Exactly one start node required
- ✅ At least one end node required
- ✅ No cycles detected (must be DAG)
- ✅ All nodes reachable from start
- ✅ All nodes can reach an end node
- ✅ Valid node types only
- ✅ All edges reference existing nodes
- ✅ No self-loops
- ✅ Valid node ID format
```

### Database Query Protection

CRUD processors use parameterized queries (prepared statements) to prevent SQL injection:

```php
// Automatically protected against SQL injection
$fields = [
    'name' => '{user_input}',  // Safely escaped
    'email' => '{email_input}'  // Safely escaped
];
```

## Error Handling

### Try-Catch in Workflows

```php
try {
    $executor->execute($workflow, $context);
} catch (InvalidWorkflowException $e) {
    // Workflow structure is invalid
    error_log("Invalid workflow: " . $e->getMessage());
} catch (RuntimeException $e) {
    // Node processing failed
    error_log("Node execution failed: " . $e->getMessage());
    // Access last error in context
    $lastError = $context->get('__last_error');
}
```

### Error Node Handling

Define an error node in your workflow to handle exceptions:

```json
{
  "id": "handle-error",
  "type": "error",
  "data": {
    "message": "Processing failed, notifying user",
    "log": true,
    "storeAs": "error_info"
  }
}
```

When an error occurs, the workflow automatically routes to the error node if it exists.

### Timeout Handling

```php
$executor = new WorkflowExecutor(
    registry: $registry,
    validator: $validator,
    expressionEvaluator: $evaluator,
    timeoutSeconds: 60  // 1 minute max
);
```

If execution exceeds the timeout, a `RuntimeException` is thrown.

## Advanced Features

### Conditional Branching

```json
{
  "nodes": [
    { "id": "start", "type": "start" },
    {
      "id": "check-amount",
      "type": "condition",
      "data": { "expression": "{amount} > 1000" }
    },
    { "id": "approve", "type": "sendEmail" },
    { "id": "review", "type": "sendEmail" },
    { "id": "end", "type": "end" }
  ],
  "edges": [
    { "source": "start", "target": "check-amount" },
    {
      "source": "check-amount",
      "target": "approve",
      "data": { "condition": "true" }
    },
    {
      "source": "check-amount",
      "target": "review",
      "data": { "condition": "false" }
    },
    { "source": "approve", "target": "end" },
    { "source": "review", "target": "end" }
  ]
}
```

### Looping

```json
{
  "id": "send-to-all",
  "type": "loop",
  "data": {
    "collection": "{recipients}",
    "itemVariable": "current_recipient",
    "maxIterations": 1000
  }
}
```

### Transaction Support

All CRUD operations support transactions:

```json
{
  "id": "create-record",
  "type": "createRecord",
  "data": {
    "model": "Order",
    "fields": { ... },
    "transaction": true,
    "compensationHandler": "rollback_order"
  }
}
```

### Custom Processors

Register custom node processors:

```php
$registry->register('myCustomNode', new MyCustomNodeProcessor());
```

## Troubleshooting

### Common Issues

**Q: "Workflow contains a cycle"**
- A: Your workflow has a loop. Workflows must be acyclic (DAGs). Remove circular connections.

**Q: "Node not reachable from start"**
- A: Some nodes are disconnected. Ensure all nodes have a path from the start node.

**Q: "Invalid email address"**
- A: The email validation failed. Verify the {email} variable contains a valid email address.

**Q: "HTTP request timeout"**
- A: The HTTP request exceeded the timeout. Increase the timeout value or check the remote API.

**Q: "Workflow execution exceeded maximum iterations"**
- A: Likely an infinite loop. Check loop configurations and iteration limits.

### Debug Logging

Enable comprehensive logging:

```php
use Monolog\Logger;
use Monolog\Handlers\StreamHandler;

$logger = new Logger('workflow');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

$executor->setLogger($logger);
```

### Performance Optimization

1. **Validation**: Validate workflows once and reuse instances
2. **Caching**: Cache parsed workflows
3. **Database**: Use connection pooling
4. **Rate Limiting**: Implement rate limits for external APIs
5. **Timeouts**: Set appropriate timeouts for long operations

## Contributing

See [CONTRIBUTING.md](../CONTRIBUTING.md) for development guidelines.

## License

MIT License - see [LICENSE](../LICENSE) file for details.
