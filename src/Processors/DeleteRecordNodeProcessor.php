<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use RuntimeException;

final class DeleteRecordNodeProcessor implements NodeProcessorInterface
{
    public function process(Node $node, ExecutionContext $context): void
    {
        $data = $node->data();
        $recordId = $data['record_id'] ?? null;

        if (!$recordId) {
            throw new RuntimeException('DeleteRecord node missing record_id');
        }

        $resolvedId = $this->resolvePlaceholders($recordId, $context);
        $context->set("deleted_record_{$resolvedId}", true);
        $context->set("last_deleted_record_id", $resolvedId);
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