<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use RuntimeException;

final class CustomNodeProcessor implements NodeProcessorInterface
{
    public function process(Node $node, ExecutionContext $context): void
    {
        $data = $node->data();
        $callback = $data['callback'] ?? null;

        if (!is_callable($callback)) {
            throw new RuntimeException('Custom node requires a valid callable in "callback" data field');
        }

        $callback($node, $context);
    }
}