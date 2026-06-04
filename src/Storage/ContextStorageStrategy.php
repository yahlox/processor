<?php

declare(strict_types=1);

namespace Yahlox\Storage;

use Yahlox\Contracts\StorageStrategyInterface;
use Yahlox\Domain\ExecutionContext;

final class ContextStorageStrategy implements StorageStrategyInterface
{
    public function create(string $model, array $payload, ExecutionContext $context, array $metadata = []): array
    {
        $recordId = uniqid('rec_', true);
        $context->set("created_{$model}_{$recordId}", $payload);
        $context->set('last_created_record', ['model' => $model, 'id' => $recordId, 'data' => $payload]);

        if (isset($metadata['storeAs'])) {
            $context->set($metadata['storeAs'], $payload);
            $context->set("{$metadata['storeAs']}_id", $recordId);
        }

        return [
            'success' => true,
            'id' => $recordId,
            'data' => $payload,
        ];
    }

    public function update(string $model, string $recordId, array $payload, ExecutionContext $context, array $metadata = []): array
    {
        $context->set("updated_record_{$recordId}", $payload);
        $context->set('last_updated_record', ['id' => $recordId, 'data' => $payload]);

        return [
            'success' => true,
            'found' => true,
            'id' => $recordId,
            'data' => $payload,
        ];
    }

    public function delete(string $model, string $recordId, ExecutionContext $context, array $metadata = []): array
    {
        $context->set("deleted_record_{$recordId}", true);
        $context->set('last_deleted_record_id', $recordId);

        return [
            'success' => true,
            'found' => true,
            'id' => $recordId,
        ];
    }

    public function find(string $model, ?string $recordId, ExecutionContext $context, array $metadata = []): ?array
    {
        if ($recordId === null) {
            return [];
        }

        return $context->get("created_{$model}_{$recordId}") ?? null;
    }
}
