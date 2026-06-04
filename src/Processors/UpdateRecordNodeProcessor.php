<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Storage\StorageHelpersTrait;
use Yahlox\Storage\StorageStrategyManager;
use RuntimeException;

/**
 * Updates an existing storage record from workflow node data.
 *
 * @package Yahlox
 */
final class UpdateRecordNodeProcessor implements NodeProcessorInterface
{
    use StorageHelpersTrait;

    private StorageStrategyManager $storageManager;

/**
 * Construct a new UpdateRecordNodeProcessor.
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
        $recordId = $data['record_id'] ?? null;
        $fields = $data['fields'] ?? [];

        if (!$recordId) {
            throw new RuntimeException('UpdateRecord node missing record_id');
        }

        $resolvedId = $this->resolvePlaceholders($recordId, $context);
        $resolved = [];
        foreach ($fields as $key => $value) {
            $resolved[$key] = $this->resolvePlaceholders($value, $context);
        }

        $data['config'] = $this->resolveStorageConfig($data, $context);
        $strategy = $this->storageManager->resolve($data, $context);
        $result = $strategy->update($data['model'] ?? 'GenericRecord', $resolvedId, $resolved, $context, $data);

        if (!($result['success'] ?? false)) {
            $context->set("updated_record_{$resolvedId}", $resolved);
            $context->set('last_updated_record', ['id' => $resolvedId, 'data' => $resolved]);
        }
    }
}
