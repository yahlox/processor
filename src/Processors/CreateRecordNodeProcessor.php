<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;

final class CreateRecordNodeProcessor implements NodeProcessorInterface
{
    public function process(Node $node, ExecutionContext $context): void
    {
        $data = $node->data();
        $model = $data['model'] ?? 'GenericRecord';
        $fields = $data['fields'] ?? [];

        // Resolve field values (support {variable})
        $resolved = [];
        foreach ($fields as $key => $value) {
            $resolved[$key] = $this->resolvePlaceholders($value, $context);
        }

        // Simulate creation – store result in context
        $recordId = uniqid('rec_', true);
        $context->set("created_{$model}_{$recordId}", $resolved);
        $context->set("last_created_record", ['model' => $model, 'id' => $recordId, 'data' => $resolved]);

        // Optionally store under a specific key
        if (isset($data['storeAs'])) {
            $context->set($data['storeAs'], $resolved);
        }
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