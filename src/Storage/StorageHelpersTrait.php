<?php

declare(strict_types=1);

namespace Yahlox\Storage;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Yahlox\Domain\ExecutionContext;

trait StorageHelpersTrait
{
    private function resolvePlaceholders(mixed $value, ExecutionContext $context): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return preg_replace_callback('/\{([a-zA-Z0-9_.]+)\}/', function ($matches) use ($context) {
            return $context->get($matches[1]) ?? '';
        }, $value);
    }

    private function guessModelClass(string $modelName): ?string
    {
        if (class_exists($modelName)) {
            return $modelName;
        }

        $candidates = [
            "App\\Models\\{$modelName}",
            "App\\{$modelName}",
            "Models\\{$modelName}",
        ];

        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function isEloquentModelClass(string $className): bool
    {
        if (!class_exists($className) || !class_exists(EloquentModel::class)) {
            return false;
        }

        return is_subclass_of($className, EloquentModel::class);
    }

    private function getShortModelName(string $className): string
    {
        $segments = explode('\\', $className);
        return end($segments) ?: $className;
    }

    private function resolveStorageConfig(array $data, ExecutionContext $context): array
    {
        $config = $data['config'] ?? [];
        $credentialsId = $config['credentials_id'] ?? $config['storage_credentials_id'] ?? null;

        if ($credentialsId === null) {
            $credentialsId = $context->get('storage_credentials_id');
        }

        if ($credentialsId !== null) {
            $credentialsId = (int)$credentialsId;
            $config = array_merge($this->loadStorageCredential($credentialsId), $config);
            $config['credentials_id'] = $credentialsId;
        }

        return $config;
    }

    private function loadStorageCredential(int $credentialId): array
    {
        $credentialClass = '\\Yahlox\\Models\\StorageChannelCredential';

        if (!class_exists($credentialClass)) {
            return [];
        }


        $credential = $credentialClass::find($credentialId);

        if ($credential === null || !$credential->is_active) {
            return [];
        }

        return $credential->connection_details ?? [];
    }
}
