<?php

declare(strict_types=1);

namespace Yahlox\Storage;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Yahlox\Domain\ExecutionContext;

/**
 * Provides shared storage helper methods for model resolution and credential lookup.
 *
 * @package Yahlox
 */
trait StorageHelpersTrait
{
    /**
     * Resolve placeholder tokens using values from the execution context.
     *
     * @param mixed $value Value to store or evaluate.
     * @param ExecutionContext $context Current workflow execution context.
     * @return mixed
     */
    private function resolvePlaceholders(mixed $value, ExecutionContext $context): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return preg_replace_callback('/\{([a-zA-Z0-9_.]+)\}/', function ($matches) use ($context) {
            return $context->get($matches[1]) ?? '';
        }, $value);
    }

    /**
     * Resolve a short model name to a fully qualified class name.
     *
     * @param string $modelName Short model name to resolve to a class.
     * @return ?string
     */
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

    /**
     * Determine if the resolved class extends the Eloquent model base.
     *
     * @param string $className Fully qualified class name to inspect.
     * @return bool
     */
    private function isEloquentModelClass(string $className): bool
    {
        if (!class_exists($className) || !class_exists(EloquentModel::class)) {
            return false;
        }

        return is_subclass_of($className, EloquentModel::class);
    }

    /**
     * Return the short class name from a fully qualified model name.
     *
     * @param string $className Fully qualified class name to inspect.
     * @return string
     */
    private function getShortModelName(string $className): string
    {
        $segments = explode('\\', $className);
        return end($segments) ?: $className;
    }

    /**
     * Perform the resolveStorageConfig operation.
     *
     * @param array $data Workflow node data used for resolution.
     * @param ExecutionContext $context Current workflow execution context.
     * @return array
     */
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

    /**
     * Load connection details from a storage credential record.
     *
     * @param int $credentialId Stored credential identifier.
     * @return array
     */
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
