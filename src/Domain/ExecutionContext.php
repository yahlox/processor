<?php

declare(strict_types=1);

namespace Yahlox\Domain;

final class ExecutionContext
{
    private array $variables = [];

    public function set(string $key, mixed $value): void
    {
        $this->variables[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $this->variables[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return array_key_exists(
            $key,
            $this->variables
        );
    }

    public function all(): array
    {
        return $this->variables;
    }
}