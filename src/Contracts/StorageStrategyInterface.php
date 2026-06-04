<?php

declare(strict_types=1);

namespace Yahlox\Contracts;

use Yahlox\Domain\ExecutionContext;

interface StorageStrategyInterface
{
    public function create(string $model, array $payload, ExecutionContext $context, array $metadata = []): array;

    public function update(string $model, string $recordId, array $payload, ExecutionContext $context, array $metadata = []): array;

    public function delete(string $model, string $recordId, ExecutionContext $context, array $metadata = []): array;

    public function find(string $model, string $recordId, ExecutionContext $context): ?array;
}
