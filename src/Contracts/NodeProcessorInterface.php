<?php

declare(strict_types=1);

namespace Yahlox\Contracts;

use Yahlox\Domain\Node;
use Yahlox\Domain\ExecutionContext;

/**
 * Defines the contract for workflow node processors.
 *
 * @package Yahlox
 */
interface NodeProcessorInterface
{
    public function process(
        Node $node,
        ExecutionContext $context
    ): void;
}