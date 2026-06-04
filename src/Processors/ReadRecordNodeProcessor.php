<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Storage\StorageHelpersTrait;
use Yahlox\Storage\StorageStrategyManager;
use RuntimeException;

final class ReadRecordNodeProcessor implements NodeProcessorInterface
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
            throw new RuntimeException('ReadRecord node missing record_id');
        }

        $resolvedId = $this->resolvePlaceholders($recordId, $context);

        $data['config'] = $this->resolveStorageConfig($data, $context);
        $strategy = $this->storageManager->resolve($data, $context);

        $found = $strategy->find($data['model'] ?? 'GenericRecord', (string)$resolvedId, $context);

        if ($found === null) {
            $context->set("found_record_{$resolvedId}", null);
            $context->set('last_read_record', ['id' => (string)$resolvedId, 'data' => null]);
            return;
        }

        if (isset($data['storeAs'])) {
            $context->set($data['storeAs'], $found);
            $context->set("{$data['storeAs']}_id", (string)$resolvedId);
        }

        $context->set('last_read_record', ['id' => (string)$resolvedId, 'data' => $found]);
    }
}
