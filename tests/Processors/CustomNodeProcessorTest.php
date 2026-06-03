<?php

declare(strict_types=1);

namespace Tests\Processors;

use PHPUnit\Framework\TestCase;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Processors\CustomNodeProcessor;

final class CustomNodeProcessorTest extends TestCase
{
    public function testCustomCallback(): void
    {
        $context = new ExecutionContext();
        $node = new Node('custom1', 'custom', [
            'callback' => function ($node, $context) {
                $context->set('custom_executed', true);
            }
        ]);

        (new CustomNodeProcessor())->process($node, $context);
        $this->assertTrue($context->get('custom_executed'));
    }
}