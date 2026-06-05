<?php

declare(strict_types=1);

namespace Yahlox\Storage;

use Yahlox\Contracts\StorageStrategyInterface;
use Yahlox\Domain\ExecutionContext;

/**
 * Simulates storage operations by recording data in workflow context.
 *
 * @package Yahlox
 */
final class ContextStorageStrategy implements StorageStrategyInterface
{
    /**
     * Create a new record in storage for the given model and payload.
     *
     * @param string $model Model class name or short alias.
     * @param array $payload Data payload for the record operation.
     * @param ExecutionContext $context Current workflow execution context.
     * @param array $metadata Optional metadata from the workflow node.
     * @return array
     */
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

    /**
     * Update an existing record in storage with the supplied payload.
     *
     * @param string $model Model class name or short alias.
     * @param string $recordId Identifier of the record to operate on.
     * @param array $payload Data payload for the record operation.
     * @param ExecutionContext $context Current workflow execution context.
     * @param array $metadata Optional metadata from the workflow node.
     * @return array
     */
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

    /**
     * Remove a record from storage by identifier.
     *
     * @param string $model Model class name or short alias.
     * @param string $recordId Identifier of the record to operate on.
     * @param ExecutionContext $context Current workflow execution context.
     * @param array $metadata Optional metadata from the workflow node.
     * @return array
     */
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

    /**
     * Retrieve a record or records from storage by ID or query metadata.
     *
     * @param string $model Model class name or short alias.
     * @param ?string $recordId Identifier of the record to operate on.
     * @param ExecutionContext $context Current workflow execution context.
     * @param array $metadata Optional metadata from the workflow node.
     * @return ?array
     */
    public function find(string $model, ?string $recordId, ExecutionContext $context, array $metadata = []): ?array
    {
        if ($recordId === null) {
            return [];
        }

        return $context->get("created_{$model}_{$recordId}") ?? null;
    }
}
