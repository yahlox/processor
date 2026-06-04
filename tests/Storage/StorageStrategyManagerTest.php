<?php

declare(strict_types=1);

namespace Tests\Storage;

use PHPUnit\Framework\TestCase;
use Yahlox\Contracts\StorageStrategyInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Storage\ContextStorageStrategy;
use Yahlox\Storage\StorageStrategyManager;

final class StorageStrategyManagerTest extends TestCase
{
    public function testDefaultStrategyIsContext(): void
    {
        $manager = StorageStrategyManager::createDefault();
        $strategy = $manager->resolve(['model' => 'GenericRecord'], new ExecutionContext());

        $this->assertInstanceOf(ContextStorageStrategy::class, $strategy);
    }

    public function testExplicitStorageNameResolvesRegisteredStrategy(): void
    {
        $manager = StorageStrategyManager::createDefault();
        $manager->register('test', new class implements StorageStrategyInterface {
            public function create(string $model, array $payload, ExecutionContext $context, array $metadata = []): array
            {
                return ['success' => true, 'id' => '1', 'data' => $payload];
            }

            public function update(string $model, string $recordId, array $payload, ExecutionContext $context, array $metadata = []): array
            {
                return ['success' => true, 'found' => true, 'id' => $recordId, 'data' => $payload];
            }

            public function delete(string $model, string $recordId, ExecutionContext $context, array $metadata = []): array
            {
                return ['success' => true, 'found' => true, 'id' => $recordId];
            }

            public function find(string $model, string $recordId, ExecutionContext $context): ?array
            {
                return ['id' => $recordId];
            }
        });

        $strategy = $manager->resolve(['storage' => 'test'], new ExecutionContext());
        $this->assertInstanceOf(StorageStrategyInterface::class, $strategy);
    }
}
