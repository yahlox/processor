<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use RuntimeException;

/**
 * Routes workflow execution based on a switch expression.
 *
 * @package Yahlox
 */
final class SwitchNodeProcessor implements NodeProcessorInterface
{
/**
 * Execute processor logic for the workflow node and update the execution context.
 *
 * @param Node $node Workflow node to process.
 * @param ExecutionContext $context Current workflow execution context.
 * @return void
 */
    public function process(Node $node, ExecutionContext $context): void
    {
        $data = $node->data();
        $expression = $data['expression'] ?? null;
        $cases = $data['cases'] ?? [];

        if (!$expression) {
            throw new RuntimeException('Switch node missing expression');
        }

        $value = $this->evaluateExpression($expression, $context);
        $targetId = $cases[$value] ?? $cases['default'] ?? null;

        if (!$targetId) {
            throw new RuntimeException("Switch node '{$node->id()}' no matching case for value '$value' and no default");
        }

        $context->set('flow.next_node_id', $targetId);
    }

/**
 * EvaluateExpression.
 * @param string $expr
 * @param ExecutionContext $context Current workflow execution context.
 * @return mixed
 */
    private function evaluateExpression(string $expr, ExecutionContext $context): mixed
    {
        // Replace {variable} placeholders
        $expr = preg_replace_callback('/\{([a-zA-Z0-9_.]+)\}/', function ($matches) use ($context) {
            $value = $context->get($matches[1]);
            if (is_string($value)) {
                return '"' . addslashes($value) . '"';
            } elseif (is_bool($value)) {
                return $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                return 'null';
            } else {
                return (string) $value;
            }
        }, $expr);

        // Allow safe characters (same as condition but without logical operators if not needed)
        if (!preg_match('/^[\s\d\.\"\'\(\)\+\-\*\/\%\=\!\<\>\&\|a-zA-Z_]+$/', $expr)) {
            throw new RuntimeException("Unsafe expression in switch: $expr");
        }

        return eval('return ' . $expr . ';');
    }
}