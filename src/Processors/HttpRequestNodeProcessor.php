<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use RuntimeException;

final class HttpRequestNodeProcessor implements NodeProcessorInterface
{
    public function process(Node $node, ExecutionContext $context): void
    {
        $data = $node->data();
        $url = $data['url'] ?? null;
        $method = strtoupper($data['method'] ?? 'GET');
        $headers = $data['headers'] ?? [];
        $body = $data['body'] ?? null;

        if (!$url) {
            throw new RuntimeException('HttpRequest node missing url');
        }

        $resolvedUrl = $this->resolvePlaceholders($url, $context);
        $resolvedHeaders = [];
        foreach ($headers as $key => $value) {
            $resolvedHeaders[$key] = $this->resolvePlaceholders($value, $context);
        }
        $resolvedBody = $body ? $this->resolvePlaceholders($body, $context) : null;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $resolvedUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->buildHeaders($resolvedHeaders));
        if ($resolvedBody && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $resolvedBody);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException("HTTP request failed: $error");
        }

        $context->set("last_http_response", [
            'url' => $resolvedUrl,
            'method' => $method,
            'status_code' => $httpCode,
            'body' => $response,
        ]);

        if (isset($data['storeResponseAs'])) {
            $context->set($data['storeResponseAs'], $response);
        }
    }

    private function resolvePlaceholders(string $value, ExecutionContext $context): string
    {
        return preg_replace_callback('/\{([a-zA-Z0-9_.]+)\}/', function ($matches) use ($context) {
            return $context->get($matches[1]) ?? '';
        }, $value);
    }

    private function buildHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $key => $value) {
            $result[] = "$key: $value";
        }
        return $result;
    }
}