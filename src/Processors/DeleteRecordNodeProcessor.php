<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Storage\StorageHelpersTrait;
use Yahlox\Storage\StorageStrategyManager;
use RuntimeException;

final class DeleteRecordNodeProcessor implements NodeProcessorInterface
{
    use StorageHelpersTrait;

    private StorageStrategyManager $storageManager;

    public function __construct(?StorageStrategyManager $storageManager = null)
    {
        $this->storageManager = $storageManager ?? StorageStrategyManager::createDefault();
    }

    public function process(Node $node, ExecutionContext $context): void
    {
        $data = $node->data();
        $recordId = $data['record_id'] ?? null;

        if (!$recordId) {
            throw new RuntimeException('DeleteRecord node missing record_id');
        }

        $resolvedId = $this->resolvePlaceholders($recordId, $context);
        $strategy = $this->storageManager->resolve($data, $context);
        $result = $strategy->delete($data['model'] ?? 'GenericRecord', $resolvedId, $context, $data);

        if (!($result['success'] ?? false)) {
            $context->set("deleted_record_{$resolvedId}", true);
            $context->set('last_deleted_record_id', $resolvedId);
        }
    }
}
