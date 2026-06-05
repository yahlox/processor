# Security Guide

## Overview

This document details the security features and best practices for using Yahlox Processor.

## Table of Contents
1. [Input Validation](#input-validation)
2. [Expression Safety](#expression-safety)
3. [SQL Injection Prevention](#sql-injection-prevention)
4. [XSS Prevention](#xss-prevention)
5. [Authentication & Authorization](#authentication--authorization)
6. [Workflow Validation](#workflow-validation)
7. [Rate Limiting](#rate-limiting)
8. [Timeout Protection](#timeout-protection)
9. [Logging & Monitoring](#logging--monitoring)
10. [Security Best Practices](#security-best-practices)

## Input Validation

### Email Validation

Email addresses in SendEmail nodes are automatically validated:

```json
{
  "type": "sendEmail",
  "data": {
    "to": "{user_email}",
    "validateEmail": true  // Default: true
  }
}
```

Invalid emails will throw an exception.

### URL Validation

URLs in HTTP request nodes are validated:

```json
{
  "type": "httpRequest",
  "data": {
    "url": "{api_endpoint}",
    "validateUrl": true  // Default: true
  }
}
```

### Custom Validation

Use the InputSanitizer for custom validation:

```php
use Yahlox\Utils\InputSanitizer;

try {
    $email = InputSanitizer::sanitize($value, 'email');
    $url = InputSanitizer::sanitize($value, 'url');
    $number = InputSanitizer::sanitize($value, 'number');
} catch (RuntimeException $e) {
    // Handle validation failure
}
```

## Expression Safety

### Safe Evaluation

The ExpressionEvaluator does NOT use PHP's `eval()` function. Instead, it:

1. **Parses expressions safely** - Only extracts variable references with `{varName}` syntax
2. **Validates operators** - Only allows comparison and logical operators
3. **Limits scope** - Can only access variables in the execution context
4. **Prevents code execution** - No arbitrary PHP code can be injected

### Variable Substitution Example

```php
$evaluator = new ExpressionEvaluator();

// Safe: Only variables from context are replaced
$result = $evaluator->evaluate(
    "Email: {email}, Name: {name}",
    $context
);
// Even if context contains malicious values, they're treated as strings
```

### Conditional Evaluation Example

```php
// Safe: Only allows comparison operators
$approved = $evaluator->evaluateCondition(
    "{amount} > 1000 && {status} == 'approved'",
    $context
);

// NOT allowed: Code injection attempts
// "{amount} > 1000 && system('rm -rf /')"  // Will throw RuntimeException
```

## SQL Injection Prevention

### Prepared Statements

CRUD processors automatically use prepared statements:

```php
// Automatically protected
$fields = [
    'name' => '{user_input}',
    'email' => '{email_input}'
];

// The framework handles parameter binding internally
```

### Elasticsearch Protection

When using Elasticsearch storage:

```php
// Query is validated and sanitized
$query = [
    'index' => '{index_name}',
    'body' => [
        'query' => [
            'match' => ['email' => '{email}']
        ]
    ]
];
```

## XSS Prevention

### HTML Sanitization

In SendEmail nodes with HTML content:

```json
{
  "type": "sendEmail",
  "data": {
    "to": "{user_email}",
    "body": "<p>Your data: {user_data}</p>",
    "htmlContent": true
  }
}
```

The processor:
- ✅ Allows safe HTML tags (`<p>`, `<strong>`, `<em>`, etc.)
- ✅ Strips dangerous tags (`<script>`, `<iframe>`, etc.)
- ✅ Removes event handlers (`onclick`, `onload`, etc.)

### Plain Text Alternative

For plain text emails (default):

```json
{
  "type": "sendEmail",
  "data": {
    "to": "{user_email}",
    "body": "Plain text content",
    "htmlContent": false  // Default
  }
}
```

Variables are automatically escaped.

## Authentication & Authorization

### Workflow Access Control

```php
// Before executing a workflow, verify user permissions
if (!$user->can('execute-workflow', $workflow)) {
    throw new AuthorizationException('Not authorized');
}

$executor->execute($workflow, $context);
```

### Context Isolation

Each workflow execution has isolated context:

```php
$context1 = new ExecutionContext();
$context1->set('user_id', 1);

$context2 = new ExecutionContext();
$context2->set('user_id', 2);

// Contexts are completely separate
$executor->execute($workflow, $context1);  // Operates with user_id=1
$executor->execute($workflow, $context2);  // Operates with user_id=2
```

### Data Access Restrictions

Restrict what data processors can access:

```php
// Only allow specific users/records
$context->set('allowed_user_ids', [1, 2, 3]);

// In a ReadRecord processor, validate access
$requestedId = $data['id'];
$allowedIds = $context->get('allowed_user_ids');
if (!in_array($requestedId, $allowedIds)) {
    throw new UnauthorizedException('Access denied');
}
```

## Workflow Validation

### Automatic Validation

All workflows are validated before execution:

```php
$validator = new WorkflowValidator();
$validator->validate($workflow);  // Throws InvalidWorkflowException if invalid
```

Validations include:
- ✅ **Start node**: Exactly one required
- ✅ **End nodes**: At least one required
- ✅ **No cycles**: DAG structure enforced
- ✅ **Reachability**: All nodes reachable from start
- ✅ **Connectivity**: All nodes can reach an end node
- ✅ **Node types**: Only recognized types allowed
- ✅ **ID format**: Valid ID format required (alphanumeric, dash, underscore)
- ✅ **Edge references**: All edges reference existing nodes

### Strict Validation Mode

```php
$parser = new ReactFlowParser(strictValidation: true);

// In strict mode:
// - Unknown node types are rejected
// - Invalid property values are rejected
// - Missing required fields are rejected
```

## Rate Limiting

### Email Rate Limiting

```php
use Yahlox\Utils\RateLimiter;

$limiter = new RateLimiter();

// Allow 100 emails per hour per user
if (!$limiter->isAllowed("emails_{$userId}", 100, 3600)) {
    throw new RateLimitException('Too many emails sent');
}
```

### API Rate Limiting

```php
// Limit HTTP requests to external APIs
if (!$limiter->isAllowed('external_api_calls', 1000, 3600)) {
    // Implement backoff or queue for later
}
```

### Custom Rate Limits

```php
$limiter->isAllowed($key, $limit, $windowSeconds);
$remaining = $limiter->getRemaining($key, $limit);
$limiter->reset($key);
```

## Timeout Protection

### Execution Timeout

```php
$executor = new WorkflowExecutor(
    registry: $registry,
    validator: $validator,
    expressionEvaluator: $evaluator,
    timeoutSeconds: 300  // 5 minutes max
);
```

Prevents infinite loops and runaway executions.

### Node Timeouts

```json
{
  "type": "httpRequest",
  "data": {
    "url": "https://api.example.com/endpoint",
    "timeout": 30,           // 30 seconds request timeout
    "connectTimeout": 10     // 10 seconds connect timeout
  }
}
```

### Loop Iteration Limits

```json
{
  "type": "loop",
  "data": {
    "collection": "{items}",
    "maxIterations": 10000
  }
}
```

## Logging & Monitoring

### Enable Debug Logging

```php
use Monolog\Logger;
use Monolog\Handlers\StreamHandler;

$logger = new Logger('workflow');
$logger->pushHandler(new StreamHandler('logs/workflow.log', Logger::DEBUG));

$executor->setLogger($logger);
```

### Monitor Security Events

```php
// Log all validation failures
$logger->warning('Workflow validation failed', [
    'workflow_id' => $workflowId,
    'error' => $error
]);

// Log unauthorized access attempts
$logger->alert('Unauthorized workflow access attempted', [
    'user_id' => $userId,
    'workflow_id' => $workflowId,
    'reason' => 'Permission denied'
]);

// Log rate limit violations
$logger->notice('Rate limit exceeded', [
    'key' => 'emails_' . $userId,
    'limit' => 100,
    'window' => 3600
]);
```

### Audit Trail

```php
// Store audit information
$context->set('audit', [
    'executed_by' => $userId,
    'execution_time' => now(),
    'status' => 'completed',
    'nodes_executed' => $executedNodeIds
]);
```

## Security Best Practices

### 1. Validate All User Input

```php
$context->set('user_email', InputSanitizer::sanitize($email, 'email'));
$context->set('amount', InputSanitizer::sanitize($amount, 'number'));
```

### 2. Use Environment Variables for Secrets

```php
// In .env
HTTP_API_TOKEN=sk_live_xxxxxxxxxxxxx

// In workflow configuration
$context->set('api_token', env('HTTP_API_TOKEN'));
```

Never store secrets in workflow JSON.

### 3. Implement Authorization Checks

```php
if (!$user->can('execute-workflow', $workflow)) {
    throw new AuthorizationException();
}
```

### 4. Use HTTPS for External APIs

```json
{
  "type": "httpRequest",
  "data": {
    "url": "https://secure-api.example.com/endpoint"
  }
}
```

HTTP is never used for sensitive data.

### 5. Enable SSL Certificate Verification

The HTTP processor automatically enables SSL verification:

```php
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
```

### 6. Implement Comprehensive Logging

```php
$executor->setLogger($logger);

// All security-relevant events are logged:
// - Authentication failures
// - Authorization denials
// - Input validation failures
// - Rate limit violations
// - Timeout events
```

### 7. Regular Security Updates

```bash
# Keep dependencies updated
composer update

# Run security checker
composer audit
```

### 8. Test Security Regularly

```bash
# Run PHPStan with strict rules
composer analyze

# Check for known vulnerabilities
composer audit
```

### 9. Isolate Sensitive Data

```php
// Store sensitive values separately from workflow context
$context->set('public_data', $publicData);
$context->set('__sensitive_data', $sensitiveData, protected: true);
```

### 10. Review Workflow JSON Before Execution

```php
// In production, review and approve workflows before execution
if ($workflow->isUserGenerated()) {
    // Require approval from admin
    if (!$workflow->isApproved()) {
        throw new WorkflowNotApprovedException();
    }
}
```

## Security Checklist

- [ ] All user input is validated with InputSanitizer
- [ ] Workflows are validated before execution
- [ ] Authorization checks are in place
- [ ] HTTPS is used for all external APIs
- [ ] Sensitive data is not logged
- [ ] Rate limiting is configured
- [ ] Timeouts are set
- [ ] SSL certificate verification is enabled
- [ ] Audit logging is enabled
- [ ] Dependencies are up to date

## Reporting Security Issues

If you discover a security vulnerability, please email security@yahlox.dev instead of using the issue tracker.

Please do not publicly disclose the vulnerability until it has been addressed.
