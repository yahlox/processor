<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;

final class EndNodeProcessor implements NodeProcessorInterface
{
    public function process(Node $node, ExecutionContext $context): void
    {
        $context->set('end_executed', true);
    }
}