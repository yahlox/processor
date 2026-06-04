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
 * Deletes a record from storage based on workflow node settings.
 *
 * @package Yahlox
 */
final class DeleteRecordNodeProcessor implements NodeProcessorInterface
{
    use StorageHelpersTrait;

    private StorageStrategyManager $storageManager;

/**
 * Construct a new DeleteRecordNodeProcessor.
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

        if (!$recordId) {
            throw new RuntimeException('DeleteRecord node missing record_id');
        }

        $resolvedId = $this->resolvePlaceholders($recordId, $context);
        $data['config'] = $this->resolveStorageConfig($data, $context);
        $strategy = $this->storageManager->resolve($data, $context);
        $result = $strategy->delete($data['model'] ?? 'GenericRecord', $resolvedId, $context, $data);

        if (!($result['success'] ?? false)) {
            $context->set("deleted_record_{$resolvedId}", true);
            $context->set('last_deleted_record_id', $resolvedId);
        }
    }
}
