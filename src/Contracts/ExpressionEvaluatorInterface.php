<?php

declare(strict_types=1);

namespace Yahlox\Contracts;

use Yahlox\Domain\ExecutionContext;

/**
 * Evaluates expressions safely within workflow execution context.
 *
 * @package Yahlox
 */
interface ExpressionEvaluatorInterface
{
    /**
     * Evaluate a variable substitution expression.
     *
     * @param string $expression Expression with {variable} syntax
     * @param ExecutionContext $context Current execution context
     * @return mixed Evaluated result
     */
    public function evaluate(string $expression, ExecutionContext $context): mixed;

    /**
     * Evaluate a condition expression and return boolean result.
     *
     * @param string $condition Condition expression
     * @param ExecutionContext $context Current execution context
     * @return bool Boolean result
     */
    public function evaluateCondition(string $condition, ExecutionContext $context): bool;
}
