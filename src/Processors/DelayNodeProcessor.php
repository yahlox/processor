<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use RuntimeException;

final class DelayNodeProcessor implements NodeProcessorInterface
{
    public function process(Node $node, ExecutionContext $context): void
    {
        $data = $node->data();
        $seconds = $data['seconds'] ?? 0;
        $milliseconds = $data['milliseconds'] ?? 0;

        if ($seconds <= 0 && $milliseconds <= 0) {
            throw new RuntimeException('Delay node requires positive seconds or milliseconds');
        }

        $usleep = (int) (($seconds * 1000000) + ($milliseconds * 1000));
        usleep($usleep);
    }
}