<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use RuntimeException;

/**
 * Evaluates a conditional expression and stores the result in the workflow context.
 *
 * @package Yahlox
 */
final class ConditionNodeProcessor implements NodeProcessorInterface
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

        if (!$expression) {
            throw new RuntimeException('Condition node missing expression');
        }

        $evaluated = $this->evaluate($expression, $context);
        $context->set("condition.{$node->id()}", $evaluated);

        $mapping = $data['branchMapping'] ?? [];
        $targetId = $mapping[$evaluated ? 'true' : 'false'] ?? null;

        if (!$targetId) {
            throw new RuntimeException("Condition node '{$node->id()}' has no target for value " . ($evaluated ? 'true' : 'false'));
        }

        $context->set('flow.next_node_id', $targetId);
    }

    /**
     * Evaluate.
     * @param string $expr
     * @param ExecutionContext $context Current workflow execution context.
     * @return bool
     */
    private function evaluate(string $expr, ExecutionContext $context): bool
    {
        $expr = $this->resolvePlaceholders($expr, $context);
        $expr = $this->normalizeExpression($expr);

        $allowed = [
            'true', 'false', 'null', 'and', 'or', 'xor', '!', '(', ')',
            '==', '===', '!=', '!==', '<', '>', '<=', '>=',
            '+', '-', '*', '/', '%', '&&', '||',
            'in_array', 'fnmatch', 'preg_match', 'str_contains',
            'str_starts_with', 'str_ends_with', 'strlen', 'is_null',
            'is_string', 'is_numeric', 'is_bool', 'is_int', 'is_float'
        ];

        $tokens = token_get_all('<?php return ' . $expr . ';');
        $cleanExpr = '';
        foreach ($tokens as $token) {
            if (is_array($token)) {
                if ($token[0] === T_VARIABLE) {
                    throw new RuntimeException('Direct variables not allowed, use {variable} notation');
                }
                if ($token[0] === T_RETURN) {
                    continue;
                }
                if ($token[0] === T_STRING && !in_array(strtolower($token[1]), $allowed, true)) {
                    throw new RuntimeException("Unsafe keyword '{$token[1]}' in expression");
                }
                if ($token[0] !== T_OPEN_TAG && $token[0] !== T_CLOSE_TAG) {
                    $cleanExpr .= $token[1];
                }
            } else {
                $cleanExpr .= $token;
            }
        }

        $cleanExpr = rtrim($cleanExpr, ';');
        $result = eval('return ' . $cleanExpr . ';');
        return (bool) $result;
    }

    /**
     * Resolve placeholder tokens using values from the execution context.
     *
     * @param string $expr Conditional expression to evaluate.
     * @param ExecutionContext $context Current workflow execution context.
     * @return string
     */
    private function resolvePlaceholders(string $expr, ExecutionContext $context): string
    {
        return preg_replace_callback('/\{([a-zA-Z0-9_.]+)\}/', function ($matches) use ($context) {
            $value = $context->get($matches[1]);
            if (is_string($value)) {
                return '"' . addslashes($value) . '"';
            }
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }
            if (is_null($value)) {
                return 'null';
            }
            return (string) $value;
        }, $expr);
    }

    /**
     * NormalizeExpression.
     * @param string $expr
     * @return string
     */
    private function normalizeExpression(string $expr): string
    {
        $expr = preg_replace('/\bIS\s+NOT\s+NULL\b/i', 'IS_NOT_NULL', $expr);
        $expr = preg_replace('/\bIS\s+NULL\b/i', 'IS_NULL', $expr);
        $expr = preg_replace('/\bNOT\s+IN\b/i', 'NOT_IN', $expr);
        $expr = preg_replace('/\bIN\b/i', 'IN', $expr);
        $expr = preg_replace('/\bNOT\s+LIKE\b/i', 'NOT_LIKE', $expr);
        $expr = preg_replace('/\bLIKE\b/i', 'LIKE', $expr);
        $expr = preg_replace('/\bBETWEEN\b/i', 'BETWEEN', $expr);

        $valueExpr = '(?:"[^"]*"|\'[^\']*\'|[a-zA-Z0-9_\.]+)';

        $expr = preg_replace_callback(
            '/(' . $valueExpr . ')\s+NOT_IN\s*\(\s*(.*?)\s*\)/i',
            fn ($matches) => '!in_array(' . $matches[1] . ', [' . $matches[2] . '], true)',
            $expr
        );

        $expr = preg_replace_callback(
            '/(' . $valueExpr . ')\s+IN\s*\(\s*(.*?)\s*\)/i',
            fn ($matches) => 'in_array(' . $matches[1] . ', [' . $matches[2] . '], true)',
            $expr
        );

        $expr = preg_replace_callback(
            '/(' . $valueExpr . ')\s+NOT_LIKE\s+(' . $valueExpr . ')/i',
            fn ($matches) => '!fnmatch(' . $this->convertLikePattern($matches[2]) . ', ' . $matches[1] . ')',
            $expr
        );

        $expr = preg_replace_callback(
            '/(' . $valueExpr . ')\s+LIKE\s+(' . $valueExpr . ')/i',
            fn ($matches) => 'fnmatch(' . $this->convertLikePattern($matches[2]) . ', ' . $matches[1] . ')',
            $expr
        );

        $expr = preg_replace_callback(
            '/(' . $valueExpr . ')\s+BETWEEN\s+(' . $valueExpr . ')\s+AND\s+(' . $valueExpr . ')/i',
            fn ($matches) => '(' . $matches[1] . ' >= ' . $matches[2] . ' && ' . $matches[1] . ' <= ' . $matches[3] . ')',
            $expr
        );

        $expr = preg_replace_callback(
            '/([a-zA-Z0-9_"\']+)\s+IS_NOT_NULL\b/i',
            fn ($matches) => '(' . $matches[1] . ' !== null)',
            $expr
        );

        $expr = preg_replace_callback(
            '/([a-zA-Z0-9_"\']+)\s+IS_NULL\b/i',
            fn ($matches) => '(' . $matches[1] . ' === null)',
            $expr
        );

        return $expr;
    }

    /**
     * ConvertLikePattern.
     * @param string $pattern
     * @return string
     */
    private function convertLikePattern(string $pattern): string
    {
        $pattern = substr($pattern, 1, -1);
        $pattern = addcslashes($pattern, '\\"');
        $pattern = str_replace(['%', '_'], ['*', '?'], $pattern);
        return '"' . $pattern . '"';
    }
}
