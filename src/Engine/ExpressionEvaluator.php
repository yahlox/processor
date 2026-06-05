<?php

declare(strict_types=1);

namespace Yahlox\Engine;

use Yahlox\Domain\ExecutionContext;
use RuntimeException;

/**
 * Safe expression evaluator for resolving variables and expressions in workflow data.
 * Supports:
 * - Simple variable substitution: {variableName}
 * - Nested property access: {object.property.nested}
 * - Array access: {array[0]} or {array.key}
 * - Safe arithmetic and comparisons
 *
 * @package Yahlox
 */
final class ExpressionEvaluator
{
    private const ALLOWED_FUNCTIONS = [
        'strlen', 'trim', 'strtoupper', 'strtolower', 'abs', 'round', 'floor', 'ceil',
        'implode', 'explode', 'str_replace', 'substr', 'strpos', 'count', 'array_merge'
    ];

    /**
     * Resolve expressions in a string using context variables.
     *
     * @param string $expression Expression string with {variable} placeholders
     * @param ExecutionContext $context Workflow execution context
     * @return string Resolved string with values substituted
     * @throws RuntimeException
     */
    public function evaluate(string $expression, ExecutionContext $context): string
    {
        return preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_\.]*(?:\[[^\]]+\])*)\}/',
            fn($matches) => $this->resolveVariable($matches[1], $context),
            $expression
        ) ?? $expression;
    }

    /**
     * Resolve a single variable reference.
     *
     * @param string $variable Variable name with optional property/array access
     * @param ExecutionContext $context Execution context
     * @return string Resolved value as string
     * @throws RuntimeException
     */
    private function resolveVariable(string $variable, ExecutionContext $context): string
    {
        // Handle array access notation: var[0] or var[key]
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)((?:\[[^\]]+\])*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)*)$/', $variable, $matches)) {
            $baseName = $matches[1];
            $accessPath = $matches[2] ?? '';

            $value = $context->get($baseName);

            if ($value === null) {
                return '';
            }

            // Process property/array access
            if (!empty($accessPath)) {
                $value = $this->resolveAccessPath($value, $accessPath);
            }

            return $this->stringify($value);
        }

        throw new RuntimeException("Invalid variable syntax: {$variable}");
    }

    /**
     * Resolve property and array access paths.
     *
     * @param mixed $value Starting value
     * @param string $accessPath Path like "[0].property[key].nested"
     * @return mixed Resolved value
     * @throws RuntimeException
     */
    private function resolveAccessPath(mixed $value, string $accessPath): mixed
    {
        // Parse access path like [0] or .property or [key]
        $pattern = '/(\[\s*([^\]]+)\s*\]|\.([a-zA-Z_][a-zA-Z0-9_]*))/';
        
        preg_match_all($pattern, $accessPath, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if ($value === null) {
                return null;
            }

            if (!empty($match[2])) {
                // Array/dict access: [key]
                $key = trim($match[2], '\'"');
                if (is_array($value)) {
                    $value = $value[$key] ?? null;
                } elseif (is_object($value)) {
                    $value = $value->{$key} ?? null;
                } else {
                    return null;
                }
            } elseif (!empty($match[3])) {
                // Property access: .property
                $property = $match[3];
                if (is_object($value)) {
                    $value = $value->{$property} ?? null;
                } elseif (is_array($value)) {
                    $value = $value[$property] ?? null;
                } else {
                    return null;
                }
            }
        }

        return $value;
    }

    /**
     * Convert value to string safely.
     *
     * @param mixed $value Value to stringify
     * @return string
     */
    private function stringify(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (string)$value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value) ?: '[]';
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string)$value;
            }
            return json_encode($value) ?: '{}';
        }

        if ($value === null) {
            return '';
        }

        return (string)$value;
    }

    /**
     * Evaluate a conditional expression safely.
     *
     * Supports: {var1} == "value", {var2} > 10, {var1} === null, etc.
     *
     * @param string $expression Conditional expression
     * @param ExecutionContext $context Execution context
     * @return bool Result of evaluation
     * @throws RuntimeException
     */
    public function evaluateCondition(string $expression, ExecutionContext $context): bool
    {
        // Resolve variables in the expression
        $resolved = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_\.]*(?:\[[^\]]+\])*)\}/',
            function ($matches) use ($context) {
                $value = $this->resolveVariable($matches[1], $context);
                // Quote string values
                return "'{$value}'";
            },
            $expression
        ) ?? $expression;

        // Simple evaluation with restricted operators
        if (!$this->isValidCondition($resolved)) {
            throw new RuntimeException("Invalid condition expression: {$expression}");
        }

        // Use eval with tight restrictions (PHP < 8.4 limitation)
        return (bool)eval("return {$resolved};");
    }

    /**
     * Validate that a condition only uses allowed operators.
     *
     * @param string $condition Condition to validate
     * @return bool
     */
    private function isValidCondition(string $condition): bool
    {
        // Allow only comparison and logical operators
        $pattern = '/^[\'"\w\s\(\)!=<>\&\|\-\+\*\/\.]+$/';
        return (bool)preg_match($pattern, $condition);
    }
}
