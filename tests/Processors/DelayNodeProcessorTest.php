<?php

declare(strict_types=1);

namespace Tests\Processors;

use PHPUnit\Framework\TestCase;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Processors\DelayNodeProcessor;

final class DelayNodeProcessorTest extends TestCase
{
    public function testDelay(): void
    {
        $context = new ExecutionContext();
        $node = new Node('d1', 'delay', ['milliseconds' => 50]);

        $start = microtime(true);
        (new DelayNodeProcessor())->process($node, $context);
        $duration = (microtime(true) - $start) * 1000;

        $this->assertGreaterThanOrEqual(45, $duration);
        $this->assertLessThan(100, $duration);
    }
}