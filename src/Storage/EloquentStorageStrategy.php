<?php

declare(strict_types=1);

namespace Yahlox\Storage;

use Yahlox\Contracts\StorageStrategyInterface;
use Yahlox\Domain\ExecutionContext;

final class EloquentStorageStrategy implements StorageStrategyInterface
{
    use StorageHelpersTrait;

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

    public function find(string $model, string $recordId, ExecutionContext $context): ?array
    {
        $modelClass = $this->guessModelClass($model);
        if ($modelClass === null || !$this->isEloquentModelClass($modelClass)) {
            return null;
        }

        $record = $modelClass::find($recordId);
        return $record !== null && method_exists($record, 'toArray') ? $record->toArray() : null;
    }
}
