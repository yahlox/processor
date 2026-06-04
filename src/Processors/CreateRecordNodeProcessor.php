<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Storage\StorageHelpersTrait;
use Yahlox\Storage\StorageStrategyManager;

/**
 * Creates a new record in storage from workflow node data.
 *
 * @package Yahlox
 */
final class CreateRecordNodeProcessor implements NodeProcessorInterface
{
    use StorageHelpersTrait;

    private StorageStrategyManager $storageManager;

/**
 * Construct a new CreateRecordNodeProcessor.
 * @param ?StorageStrategyManager $storageManager
 * @return void
 */
    public function __construct(?StorageStrategyManager $storageManager = null)
    {
        $this->storageManager = $storageManager ?? StorageStrategyManager::createDefault();
    }

/**
 * Execute processor logic for the workflow node and update the execution context.
 *
 * @param Node $node Workflow node to process.
 * @param ExecutionContext $context Current workflow execution context.
 * @return void
 */
    public function process(Node $node, ExecutionContext $context): void
    {
        $data = $node->data();
        $model = $data['model'] ?? 'GenericRecord';
        $fields = $data['fields'] ?? [];

        $resolved = [];
        foreach ($fields as $key => $value) {
            $resolved[$key] = $this->resolvePlaceholders($value, $context);
        }

        $data['config'] = $this->resolveStorageConfig($data, $context);
        $strategy = $this->storageManager->resolve($data, $context);
        $result = $strategy->create($model, $resolved, $context, $data);

        if (!($result['success'] ?? false) && isset($data['storeAs'])) {
            $context->set($data['storeAs'], $resolved);
            $context->set("{$data['storeAs']}_id", $result['id'] ?? uniqid('rec_', true));
        }
    }
}
