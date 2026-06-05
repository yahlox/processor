# Yahlox Processor - Secure PHP Workflow Engine

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.0-blue)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-10%2C%2011%2C%2012%2C%2013-green)](https://laravel.com)
[![License](https://img.shields.io/badge/License-MIT-yellow)](LICENSE)
[![Tests](https://github.com/yahlox/processor/workflows/CI/badge.svg)](https://github.com/yahlox/processor/actions)

A powerful, secure PHP workflow engine that parses ReactFlow JSON diagrams and executes them as dynamic workflows. Perfect for automating complex business processes in Laravel applications.

## 🚀 Features

### Core Capabilities
- ✅ **ReactFlow JSON Parsing** - Import workflows directly from React Flow designer
- ✅ **15+ Pre-built Nodes** - Email, SMS, HTTP, CRUD, conditions, loops, and more
- ✅ **Comprehensive Validation** - Cycle detection, connectivity checks, schema validation
- ✅ **Safe Expression Evaluation** - Secure variable substitution without code injection risks
- ✅ **Error Handling & Recovery** - Error nodes, fallback paths, graceful degradation

### Security & Reliability
- ✅ **Input Sanitization** - Automatic sanitization for emails, URLs, and user input
- ✅ **SQL Injection Protection** - Prepared statements for all database operations
- ✅ **XSS Prevention** - HTML content sanitization and escaping
- ✅ **Transaction Support** - ACID compliance for critical workflows
- ✅ **Saga Pattern** - Compensating transactions for distributed operations
- ✅ **Timeout Protection** - Prevents infinite loops and runaway executions
- ✅ **Rate Limiting** - Built-in rate limiting utilities
- ✅ **Retry Logic** - Exponential backoff for failed operations

### Observability
- ✅ **Comprehensive Logging** - PSR-3 logger integration for all operations
- ✅ **Execution Tracking** - Detailed logging of workflow execution
- ✅ **Error Context** - Rich context information in error messages
- ✅ **Audit Trail** - Track who executed what and when

### Developer Experience
- ✅ **Easy Integration** - Simple Laravel service provider
- ✅ **Well Documented** - Comprehensive guides and examples
- ✅ **Type Hints** - Full PHP 8 type support
- ✅ **Extensible** - Custom processors and strategies
- ✅ **PHP 8.0+** - Modern PHP with backward compatibility
- ✅ **Multi-Laravel** - Works with Laravel 10-13

## 📦 Installation

```bash
composer require yahlox/processor
```

### Requirements
- PHP 8.0+
- Laravel 10+
- JSON extension
- cURL extension

## 🎯 Quick Start

```php
use Yahlox\Parser\ReactFlowParser;
use Yahlox\Engine\WorkflowValidator;
use Yahlox\Engine\WorkflowExecutor;
use Yahlox\Engine\ExpressionEvaluator;
use Yahlox\Registry\NodeProcessorRegistry;
use Yahlox\Domain\ExecutionContext;

// 1. Parse workflow from ReactFlow
$parser = new ReactFlowParser(strictValidation: true);
$workflow = $parser->parse($jsonPayload);

// 2. Validate workflow
$validator = new WorkflowValidator();
$validator->validate($workflow);

// 3. Create executor
$registry = new NodeProcessorRegistry();
$executor = new WorkflowExecutor(
    registry: $registry,
    validator: $validator,
    expressionEvaluator: new ExpressionEvaluator(),
    timeoutSeconds: 300
);

// 4. Set up context
$context = new ExecutionContext();
$context->set('user_email', 'user@example.com');
$context->set('order_id', 12345);

// 5. Execute workflow
try {
    $executor->execute($workflow, $context);
    echo "Success!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## 📚 Documentation

- **[Complete User Guide](docs/GUIDE.md)** - Detailed documentation with examples
- **[Security Guide](docs/SECURITY.md)** - Security best practices and features
- **[Migration Guide](MIGRATION.md)** - Upgrading from v1.x to v2.x
- **[Node Catalog](docs/GUIDE.md#node-types)** - All available node types

## 🏗️ Workflow Structure

A workflow consists of **nodes** (operations) and **edges** (connections):

```json
{
  "nodes": [
    { "id": "start", "type": "start", "data": {} },
    { "id": "email", "type": "sendEmail", "data": {
      "to": "{user_email}",
      "subject": "Welcome",
      "body": "Hello {name}!"
    } },
    { "id": "end", "type": "end", "data": {} }
  ],
  "edges": [
    { "source": "start", "target": "email" },
    { "source": "email", "target": "end" }
  ]
}
```

## 🔧 Node Types

| Type | Purpose | Example |
|------|---------|---------|
| `start` | Workflow entry point | Begin execution |
| `end` | Workflow exit point | Complete execution |
| `condition` | Branching logic | If amount > 1000 |
| `switch` | Multiple branches | Switch on status |
| `loop` | Iterate collection | Process each item |
| `createRecord` | Insert database record | Create user |
| `readRecord` | Fetch records | Get order |
| `updateRecord` | Modify records | Update status |
| `deleteRecord` | Remove records | Delete user |
| `sendEmail` | Send emails | Email notification |
| `sendSms` | Send SMS | SMS alert |
| `sendNotification` | In-app notifications | Notify user |
| `httpRequest` | HTTP API calls | Call webhook |
| `delay` | Pause execution | Wait 5 seconds |
| `error` | Error handler | Handle failures |
| `custom` | Custom logic | Run custom code |

## 🔐 Security Highlights

### 1. Safe Variable Substitution
```php
// NOT vulnerable to injection
$evaluator->evaluate("{email} at {company}", $context);
// Even if {email} contains PHP code, it's treated as a string
```

### 2. Input Validation
```php
use Yahlox\Utils\InputSanitizer;

$email = InputSanitizer::sanitize($value, 'email');  // Validates email format
$url = InputSanitizer::sanitize($value, 'url');      // Validates URL format
```

### 3. SQL Injection Protection
```json
{
  "type": "createRecord",
  "data": {
    "fields": {
      "name": "{user_input}",  // Automatically parameterized
      "email": "{email_input}"
    }
  }
}
```

### 4. XSS Prevention
```json
{
  "type": "sendEmail",
  "data": {
    "body": "<p>User data: {user_data}</p>",
    "htmlContent": true  // Automatically sanitized
  }
}
```

### 5. Timeout Protection
```php
$executor = new WorkflowExecutor(
    // ... other params
    timeoutSeconds: 300  // Max 5 minutes
);
```

### 6. Workflow Validation
All workflows are validated:
- ✅ Exactly one start node
- ✅ At least one end node
- ✅ No cycles (must be DAG)
- ✅ All nodes reachable
- ✅ Valid node types only

## 📊 Advanced Features

### Transaction Support
```json
{
  "type": "createRecord",
  "data": {
    "transaction": true,
    "connection": "default"
  }
}
```

### Retry Logic
```php
use Yahlox\Utils\RetryPolicy;

$policy = new RetryPolicy(
    maxAttempts: 3,
    initialDelayMs: 100,
    backoffMultiplier: 2.0
);
$result = $policy->execute($operation);
```

### Rate Limiting
```php
use Yahlox\Utils\RateLimiter;

$limiter = new RateLimiter();
if (!$limiter->isAllowed('email_sends', 100, 3600)) {
    throw new RateLimitException('Too many emails');
}
```

### Conditional Routing
```json
{
  "edges": [{
    "source": "check",
    "target": "approve",
    "data": {
      "condition": "{amount} > 1000 && {status} == 'active'"
    }
  }]
}
```

### Error Handling
```json
{
  "type": "error",
  "data": {
    "message": "Processing failed",
    "log": true,
    "stopExecution": false,
    "storeAs": "error_info"
  }
}
```

## 🧪 Testing

```bash
# Run tests
composer test

# With coverage
composer test:coverage

# Static analysis
composer analyze

# Code quality checks
composer rector
```

## 📝 Configuration

### PHPStan
Static analysis is configured in `phpstan.neon` at level 8 (maximum strictness).

### PHP CS Fixer
Code style is PSR-12 with strict rules. Run:
```bash
composer fix
```

### GitHub Actions
CI/CD pipeline runs on push and PR:
- Tests on PHP 8.0-8.3
- PHPStan analysis
- Code style checks
- Security scanning

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

## 🆘 Support

- 📖 [Full Documentation](docs/GUIDE.md)
- 🔒 [Security Guide](docs/SECURITY.md)
- 📱 [Migration Guide](MIGRATION.md)
- 🐛 [Issues](https://github.com/yahlox/processor/issues)
- 💬 Email: support@yahlox.dev

## 🙏 Acknowledgments

- Built with PHP 8 best practices
- Inspired by ReactFlow and modern workflow engines
- Security-first design approach

## 📋 Changelog

See [MIGRATION.md](MIGRATION.md) for detailed changelog and upgrade information.

---

**Made with ❤️ by the Yahlox team**

