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

final class YahloxServiceProvider extends ServiceProvider
{
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

    public function boot(): void
    {
        $this->publishMigrations([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ]);
    }
}
