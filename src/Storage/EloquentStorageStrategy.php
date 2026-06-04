<?php

declare(strict_types=1);

namespace Yahlox\Storage;

use Yahlox\Contracts\StorageStrategyInterface;
use Yahlox\Domain\ExecutionContext;

/**
 * Persists and reads records using Laravel Eloquent models.
 *
 * @package Yahlox
 */
final class EloquentStorageStrategy implements StorageStrategyInterface
{
    use StorageHelpersTrait;

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
        $modelClass = $this->guessModelClass($model);
        if ($modelClass === null || !$this->isEloquentModelClass($modelClass)) {
            return ['success' => false, 'id' => '', 'data' => $payload];
        }

        $record = new $modelClass();
        if (method_exists($record, 'forceFill')) {
            $record->forceFill($payload);
        } else {
            foreach ($payload as $key => $value) {
                $record->{$key} = $value;
            }
        }

        $record->save();
        $recordId = (string)$record->getKey();
        $data = method_exists($record, 'toArray') ? $record->toArray() : [];
        $modelShortName = $this->getShortModelName($modelClass);

        $context->set("created_{$modelShortName}_{$recordId}", $data);
        $context->set('last_created_record', ['model' => $modelShortName, 'id' => $recordId, 'data' => $data]);

        if (isset($metadata['storeAs'])) {
            $context->set($metadata['storeAs'], $data);
            $context->set("{$metadata['storeAs']}_id", $recordId);
        }

        return [
            'success' => true,
            'id' => $recordId,
            'data' => $data,
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
        $modelClass = $this->guessModelClass($model);
        if ($modelClass === null || !$this->isEloquentModelClass($modelClass)) {
            return ['success' => false, 'found' => false, 'id' => $recordId, 'data' => $payload];
        }

        $record = $modelClass::find($recordId);
        if ($record === null) {
            return ['success' => false, 'found' => false, 'id' => $recordId, 'data' => $payload];
        }

        if (method_exists($record, 'forceFill')) {
            $record->forceFill($payload);
        } else {
            foreach ($payload as $key => $value) {
                $record->{$key} = $value;
            }
        }

        $record->save();
        $data = method_exists($record, 'toArray') ? $record->toArray() : [];
        $context->set("updated_record_{$recordId}", $data);
        $context->set('last_updated_record', ['id' => $recordId, 'data' => $data]);

        return [
            'success' => true,
            'found' => true,
            'id' => $recordId,
            'data' => $data,
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
        $modelClass = $this->guessModelClass($model);
        if ($modelClass === null || !$this->isEloquentModelClass($modelClass)) {
            return ['success' => false, 'found' => false, 'id' => $recordId];
        }

        $record = $modelClass::find($recordId);
        if ($record === null) {
            return ['success' => false, 'found' => false, 'id' => $recordId];
        }

        $record->delete();
        $context->set("deleted_record_{$recordId}", true);
        $context->set('last_deleted_record_id', $recordId);

        return ['success' => true, 'found' => true, 'id' => $recordId];
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
        $modelClass = $this->guessModelClass($model);
        if ($modelClass === null || !$this->isEloquentModelClass($modelClass)) {
            return null;
        }

        if ($recordId !== null && $recordId !== '') {
            $record = $modelClass::find($recordId);

            return $record !== null && method_exists($record, 'toArray') ? $record->toArray() : null;
        }

        $query = $modelClass::query();
        $conditions = $metadata['query'] ?? [];

        if (is_string($conditions) && $conditions !== '') {
            $query->whereRaw($conditions);
        } elseif (is_array($conditions)) {
            foreach ($conditions as $key => $value) {
                if (is_int($key)) {
                    if (is_array($value)) {
                        if (count($value) === 3) {
                            [$column, $operator, $val] = $value;
                            $query->where($column, $operator, $this->resolvePlaceholders($val, $context));
                            continue;
                        }

                        if (count($value) === 2) {
                            [$column, $val] = $value;
                            $query->where($column, $this->resolvePlaceholders($val, $context));
                            continue;
                        }
                    }

                    if (is_string($value)) {
                        $query->whereRaw($value);
                    }
                } elseif ($key === 'raw' && is_string($value)) {
                    $query->whereRaw($value);
                } elseif ($key === 'or' && is_array($value)) {
                    $query->where(function ($builder) use ($value, $context) {
                        foreach ($value as $condition) {
                            if (!is_array($condition)) {
                                continue;
                            }

                            if (count($condition) === 3) {
                                [$column, $operator, $val] = $condition;
                                $builder->orWhere($column, $operator, $this->resolvePlaceholders($val, $context));
                                continue;
                            }

                            if (count($condition) === 2) {
                                [$column, $val] = $condition;
                                $builder->orWhere($column, $this->resolvePlaceholders($val, $context));
                            }
                        }
                    });
                } else {
                    $query->where($key, $this->resolvePlaceholders($value, $context));
                }
            }
        }

        $records = $query->get();

        return $records->map(static fn ($record) => method_exists($record, 'toArray') ? $record->toArray() : [])->all();
    }
}
