<?php

declare(strict_types=1);

namespace Yahlox\Contracts;

use Yahlox\Domain\Node;
use Yahlox\Domain\ExecutionContext;

interface NodeProcessorInterface
{
    public function process(
        Node $node,
        ExecutionContext $context
    ): void;
}