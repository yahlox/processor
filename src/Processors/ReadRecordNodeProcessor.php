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
        $recordId = $recordId !== null ? (string)$this->resolvePlaceholders($recordId, $context) : null;
        $recordId = trim((string)$recordId) === '' ? null : $recordId;

        $data['config'] = $this->resolveStorageConfig($data, $context);
        $strategy = $this->storageManager->resolve($data, $context);

        $found = $strategy->find($data['model'] ?? 'GenericRecord', $recordId, $context, $data);

        if ($found === null) {
            if ($recordId !== null) {
                $context->set("found_record_{$recordId}", null);
                $context->set('last_read_record', ['id' => $recordId, 'data' => null]);
            }
            return;
        }

        if (isset($data['storeAs'])) {
            $context->set($data['storeAs'], $found);
            if ($recordId !== null) {
                $context->set("{$data['storeAs']}_id", $recordId);
            }
        }

        $context->set('last_read_record', ['id' => $recordId, 'data' => $found]);
    }
}
