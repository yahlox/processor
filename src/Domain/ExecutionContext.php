<?php

declare(strict_types=1);

namespace Yahlox\Domain;

/**
 * Stores workflow execution variables during runtime.
 *
 * @package Yahlox
 */
final class ExecutionContext
{
    private array $variables = [];

/**
 * Store a named value in the execution context.
 *
 * @param string $key Context key name.
 * @param mixed $value Value to store or evaluate.
 * @return void
 */
    public function set(string $key, mixed $value): void
    {
        $this->variables[$key] = $value;
    }

/**
 * Retrieve a value from the execution context by key.
 *
 * @param string $key Context key name.
 * @return mixed
 */
    public function get(string $key): mixed
    {
        return $this->variables[$key] ?? null;
    }

/**
 * Check whether a named instance is registered.
 *
 * @param string $key Context key name.
 * @return bool
 */
    public function has(string $key): bool
    {
        return array_key_exists(
            $key,
            $this->variables
        );
    }

/**
 * Return all stored execution context values.
 *
 * @return array
 */
    public function all(): array
    {
        return $this->variables;
    }
}