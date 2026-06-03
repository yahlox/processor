<?php

declare(strict_types=1);

namespace Tests\Processors;

use PHPUnit\Framework\TestCase;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Processors\HttpRequestNodeProcessor;

/**
 * @requires extension curl
 */
final class HttpRequestNodeProcessorTest extends TestCase
{
    public function testHttpGet(): void
    {
        $context = new ExecutionContext();
        $node = new Node('http1', 'http_request', [
            'url' => 'https://httpbin.org/get',
            'method' => 'GET',
            'storeResponseAs' => 'api_response'
        ]);

        $processor = new HttpRequestNodeProcessor();
        $processor->process($node, $context);

        $response = $context->get('last_http_response');
        $this->assertSame(200, $response['status_code']);
        $this->assertNotNull($context->get('api_response'));
    }
}