<?php

declare(strict_types=1);

namespace Yahlox\Storage;

use Yahlox\Contracts\StorageStrategyInterface;
use Yahlox\Domain\ExecutionContext;
use RuntimeException;

final class StorageStrategyManager
{
    use StorageHelpersTrait;

    private array $strategies = [];
    private string $defaultStrategy;

    public function __construct(array $strategies = [], string $defaultStrategy = 'context')
    {
        foreach ($strategies as $name => $strategy) {
            $this->register($name, $strategy);
        }

        $this->defaultStrategy = $defaultStrategy;
    }

    public static function createDefault(): self
    {
        return new self([
            'context' => new ContextStorageStrategy(),
            'eloquent' => new EloquentStorageStrategy(),
        ], 'context');
    }

    public function register(string $name, StorageStrategyInterface $strategy): void
    {
        $this->strategies[$name] = $strategy;
    }

    public function get(string $name): StorageStrategyInterface
    {
        if (!isset($this->strategies[$name])) {
            throw new RuntimeException(sprintf('Storage strategy [%s] not found.', $name));
        }

        return $this->strategies[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->strategies[$name]);
    }

    public function resolve(array $data, ExecutionContext $context): StorageStrategyInterface
    {
        $storageName = $data['storage'] ?? null;
        if (is_string($storageName) && $this->has($storageName)) {
            return $this->get($storageName);
        }

        if (isset($data['model'])) {
            $model = $this->resolvePlaceholders($data['model'], $context);
            $modelClass = is_string($model) && $model !== '' ? $this->guessModelClass($model) : null;
            if ($modelClass !== null && $this->isEloquentModelClass($modelClass) && $this->has('eloquent')) {
                return $this->get('eloquent');
            }
        }

        return $this->get($this->defaultStrategy);
    }
}
