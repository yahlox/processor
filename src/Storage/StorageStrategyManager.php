<?php

declare(strict_types=1);

namespace Yahlox\Storage;

use Yahlox\Contracts\StorageStrategyInterface;
use Yahlox\Domain\ExecutionContext;
use RuntimeException;

/**
 * Manages and resolves available storage strategies for workflow nodes.
 *
 * @package Yahlox
 */
final class StorageStrategyManager
{
    use StorageHelpersTrait;

    private array $strategies = [];
    private string $defaultStrategy;

/**
 * Construct a new StorageStrategyManager.
 * @param array $strategies
 * @param string $defaultStrategy
 * @return void
 */
    public function __construct(array $strategies = [], string $defaultStrategy = 'context')
    {
        foreach ($strategies as $name => $strategy) {
            $this->register($name, $strategy);
        }

        $this->defaultStrategy = $defaultStrategy;
    }

/**
 * Create the default manager with built-in strategies.
 *
 * @return self
 */
    public static function createDefault(): self
    {
        return new self([
            'context' => new ContextStorageStrategy(),
            'eloquent' => new EloquentStorageStrategy(),
        ], 'context');
    }

/**
 * Register a strategy by alias.
 *
 * @param string $name Registry or strategy name.
 * @param StorageStrategyInterface $strategy Resolved strategy instance.
 * @return void
 */
    public function register(string $name, StorageStrategyInterface $strategy): void
    {
        $this->strategies[$name] = $strategy;
    }

/**
 * Return the named registered strategy.
 *
 * @param string $name Registry or strategy name.
 * @return StorageStrategyInterface
 */
    public function get(string $name): StorageStrategyInterface
    {
        if (!isset($this->strategies[$name])) {
            throw new RuntimeException(sprintf('Storage strategy [%s] not found.', $name));
        }

        return $this->strategies[$name];
    }

/**
 * Check whether a named instance is registered.
 *
 * @param string $name Registry or strategy name.
 * @return bool
 */
    public function has(string $name): bool
    {
        return isset($this->strategies[$name]);
    }

/**
 * Resolve the correct strategy for the given workflow node data and context.
 *
 * @param array $data Workflow node data used for resolution.
 * @param ExecutionContext $context Current workflow execution context.
 * @return StorageStrategyInterface
 */
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
