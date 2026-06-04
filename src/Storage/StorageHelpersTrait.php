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
}
