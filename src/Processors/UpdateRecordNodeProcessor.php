<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use RuntimeException;

final class UpdateRecordNodeProcessor implements NodeProcessorInterface
{
    public function process(Node $node, ExecutionContext $context): void
    {
        $data = $node->data();
        $recordId = $data['record_id'] ?? null;
        $fields = $data['fields'] ?? [];

        if (!$recordId) {
            throw new RuntimeException('UpdateRecord node missing record_id');
        }

        $resolved = [];
        foreach ($fields as $key => $value) {
            $resolved[$key] = $this->resolvePlaceholders($value, $context);
        }

        // Simulate update – store updated data in context
        $context->set("updated_record_{$recordId}", $resolved);
        $context->set("last_updated_record", ['id' => $recordId, 'data' => $resolved]);
    }

    private function resolvePlaceholders($value, ExecutionContext $context): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        return preg_replace_callback('/\{([a-zA-Z0-9_.]+)\}/', function ($matches) use ($context) {
            return $context->get($matches[1]) ?? '';
        }, $value);
    }
}