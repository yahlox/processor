# CHANGELOG & MIGRATION GUIDE

## Version 2.0.0 - Major Security & Stability Release

### Breaking Changes

#### 1. Expression Evaluation
**Old Code:**
```php
preg_replace_callback('/\{([a-zA-Z0-9_.]+)\}/', fn($m) => $context->get($m[1]), $value);
```

**New Code:**
```php
$evaluator = new ExpressionEvaluator();
$evaluator->evaluate($value, $context);
```

**Why:** The old regex-based approach was vulnerable to injection attacks. The new `ExpressionEvaluator` provides safe, sandboxed evaluation.

#### 2. WorkflowExecutor Constructor
**Old Code:**
```php
$executor = new WorkflowExecutor($registry, $validator);
$executor->execute($workflow, $context);
```

**New Code:**
```php
$executor = new WorkflowExecutor(
    registry: $registry,
    validator: $validator,
    expressionEvaluator: $evaluator,
    logger: $logger,
    timeoutSeconds: 300
);
$executor->execute($workflow, $context);
```

**Why:** Added required dependencies for secure execution. Logger and timeout are optional but recommended.

#### 3. Flow Control
**Old Code:**
```php
$context->set('flow.next_node_id', $nextNodeId);
```

**New Code:**
```php
$context->set('__next_node_id', $nextNodeId);
```

**Why:** Used internal conventions for flow control to prevent conflicts with user data.

#### 4. Node and Edge Classes
**Old Code:**
```php
new Node(id: $id, type: $type, data: $data);
new Edge(source: $src, target: $tgt);
```

**New Code:**
```php
new Node(id: $id, type: $type, data: $data, position: $pos, metadata: $meta);
new Edge(source: $src, target: $tgt, metadata: $meta);
```

**Why:** Added support for React Flow position and metadata (conditions).

#### 5. ReactFlowParser
**Old Code:**
```php
$parser = new ReactFlowParser();
$workflow = $parser->parse($payload);
```

**New Code:**
```php
$parser = new ReactFlowParser(strictValidation: true);
$workflow = $parser->parse($payload);
```

**Why:** Added schema validation. Set `strictValidation: false` for backward compatibility.

### New Features

#### Input Sanitization
```php
use Yahlox\Utils\InputSanitizer;

$email = InputSanitizer::sanitize('user@example.com', 'email');
$url = InputSanitizer::sanitize('https://example.com', 'url');
```

#### Rate Limiting
```php
use Yahlox\Utils\RateLimiter;

$limiter = new RateLimiter();
if ($limiter->isAllowed('email_sends', 100, 3600)) {
    // Send email
}
```

#### Retry Logic
```php
use Yahlox\Utils\RetryPolicy;

$policy = new RetryPolicy(maxAttempts: 3);
$result = $policy->execute($operation);
```

#### Transaction Support
```json
{
  "type": "createRecord",
  "data": {
    "transaction": true,
    "connection": "default"
  }
}
```

#### Saga/Compensation Pattern
```json
{
  "type": "createRecord",
  "data": {
    "compensationHandler": "rollback_user_creation"
  }
}
```

#### Enhanced Validation
- Cycle detection (DFS algorithm)
- Connectivity checking
- Node reachability validation
- Schema validation

#### Error Handling
```json
{
  "type": "error",
  "data": {
    "message": "Processing failed",
    "log": true,
    "stopExecution": false
  }
}
```

#### Logging & Observability
```php
$logger = new Logger('workflow');
$executor->setLogger($logger);
// All operations are logged
```

### Security Fixes

1. ✅ Replaced unsafe placeholder resolution with ExpressionEvaluator
2. ✅ Added comprehensive input validation and sanitization
3. ✅ Implemented HTTP timeout and retry protection
4. ✅ Added rate limiting utilities
5. ✅ Enforced SSL verification in HTTP requests
6. ✅ Added schema validation to ReactFlowParser
7. ✅ Implemented cycle detection in WorkflowValidator
8. ✅ Protected against expression injection attacks

### PHP Version

**Old:** `^8.4`
**New:** `^8.0` (supports 8.0, 8.1, 8.2, 8.3, 8.4)

**Benefits:** 
- Better compatibility with existing Laravel projects
- Broader adoption base

### Laravel Version

Now supports:
- Laravel 10 (^10.0)
- Laravel 11 (^11.0)  
- Laravel 12 (^12.0)
- Laravel 13 (^13.0)

**Old:** Only Laravel 13

### Migration Steps

#### Step 1: Update Composer
```bash
composer require yahlox/processor:^2.0
```

#### Step 2: Update Processor Registration

**Old:**
```php
$processor = new SendEmailNodeProcessor();
```

**New:**
```php
$processor = new SendEmailNodeProcessor(
    channelManager: $channelManager,
    expressionEvaluator: $evaluator,
    logger: $logger
);
```

#### Step 3: Update Workflow Execution

**Old:**
```php
$executor = new WorkflowExecutor($registry, $validator);
$executor->execute($workflow, $context);
```

**New:**
```php
$executor = new WorkflowExecutor(
    registry: $registry,
    validator: $validator,
    expressionEvaluator: new ExpressionEvaluator(),
    logger: $logger,
    timeoutSeconds: 300
);
$executor->execute($workflow, $context);
```

#### Step 4: Update Workflow JSON

If using conditional edges, add metadata:

**Old:**
```json
{
  "source": "node1",
  "target": "node2"
}
```

**New:**
```json
{
  "source": "node1",
  "target": "node2",
  "data": {
    "condition": "{amount} > 1000"
  }
}
```

#### Step 5: Enable Validation

```php
$parser = new ReactFlowParser(strictValidation: true);
$workflow = $parser->parse($payload);

$validator = new WorkflowValidator();
$validator->validate($workflow);  // Throws InvalidWorkflowException if invalid
```

#### Step 6: Add Error Handling

```php
try {
    $executor->execute($workflow, $context);
} catch (InvalidWorkflowException $e) {
    // Handle invalid workflow
} catch (RuntimeException $e) {
    // Handle execution error
    $lastError = $context->get('__last_error');
}
```

### Deprecations

The following are deprecated and will be removed in v3.0:

1. `flow.next_node_id` context key → Use `__next_node_id`
2. Unsafe placeholder resolution in processors → Use ExpressionEvaluator
3. No validation mode → Always validate in v3.0

### Configuration Files Added

1. `phpstan.neon` - Static analysis configuration
2. `rector.php` - Automated refactoring configuration
3. `.php-cs-fixer.php` - Code style configuration
4. `.github/workflows/ci.yml` - CI/CD pipeline

### Testing

Run the new test suite:

```bash
composer test                    # Run PHPUnit
composer test:coverage          # With coverage report
composer analyze                # Run PHPStan
composer rector                 # Check code quality
```

### Documentation

Comprehensive documentation available:

- [docs/GUIDE.md](docs/GUIDE.md) - Complete user guide with examples
- [docs/SECURITY.md](docs/SECURITY.md) - Security best practices
- This file - Migration guide

### Support

For questions or issues:
1. Check the documentation first
2. Open an issue on GitHub
3. Email: support@yahlox.dev

### Upgrade Checklist

- [ ] Updated `composer.json` dependencies
- [ ] Updated processor initialization with new parameters
- [ ] Updated workflow execution code
- [ ] Added error handling
- [ ] Updated workflow JSON for conditional edges
- [ ] Enabled strict validation
- [ ] Added logging
- [ ] Tested with new version
- [ ] Reviewed security documentation

### Thank You

Thank you for using Yahlox! This major release brings significant security improvements and stability enhancements. We believe it strikes the right balance between functionality, security, and ease of use.

For breaking change questions, please refer to this guide or open an issue.
