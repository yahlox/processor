<?php

declare(strict_types=1);

namespace Yahlox\Laravel;

use Illuminate\Support\ServiceProvider;
use Yahlox\Engine\WorkflowExecutor;
use Yahlox\Engine\WorkflowValidator;
use Yahlox\Parser\ReactFlowParser;
use Yahlox\Registry\NodeProcessorRegistry;
use Yahlox\Storage\StorageStrategyManager;
use Yahlox\Send\SendChannelStrategyManager;

/**
 * Registers Yahlox services in Laravel and publishes package assets.
 *
 * @package Yahlox
 */
final class YahloxServiceProvider extends ServiceProvider
{
/**
 * Register Yahlox services into the Laravel application container.
 *
 * @return void
 */
    public function register(): void
    {
        $this->app->singleton(NodeProcessorRegistry::class, function () {
            return new NodeProcessorRegistry();
        });

        $this->app->singleton(ReactFlowParser::class, function () {
            return new ReactFlowParser();
        });

        $this->app->singleton(WorkflowValidator::class, function () {
            return new WorkflowValidator();
        });

        $this->app->singleton(StorageStrategyManager::class, function () {
            return StorageStrategyManager::createDefault();
        });

        $this->app->singleton(SendChannelStrategyManager::class, function () {
            return SendChannelStrategyManager::createDefault();
        });

        $this->app->singleton(WorkflowExecutor::class, function ($app) {
            return new WorkflowExecutor(
                $app->make(NodeProcessorRegistry::class),
                $app->make(WorkflowValidator::class)
            );
        });

        $this->app->singleton(\Yahlox\YahloxLibrary::class, function ($app) {
            return new \Yahlox\YahloxLibrary(
                $app->make(ReactFlowParser::class),
                $app->make(WorkflowExecutor::class)
            );
        });
    }

/**
 * Configure package publishing and load migrations when running in console.
 *
 * @return void
 */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $migrationsPath = __DIR__ . '/../../database/migrations';

            // Get all migration files and publish them
            $migrations = glob($migrationsPath . '/*.php');
            $migrationPublish = [];

            foreach ($migrations as $migration) {
                $migrationPublish[$migration] = database_path('migrations/' . basename($migration));
            }

            if (!empty($migrationPublish)) {
                $this->publishes($migrationPublish, 'yahlox-migrations');
            }


            // Shared publish group for convenience
            if (!empty($migrationPublish) || !empty($modelPublish)) {
                $this->publishes(array_merge($migrationPublish), 'yahlox');
            }

            // Also auto-load migrations
            $this->loadMigrationsFrom($migrationsPath);
        }
    }
}
