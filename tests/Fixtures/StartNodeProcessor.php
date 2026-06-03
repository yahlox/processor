<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;

final class StartNodeProcessor implements NodeProcessorInterface
{
    public function process(
        Node $node,
        ExecutionContext $context
    ): void {

        $context->set(
            'start_executed',
            true
        );
    }
}