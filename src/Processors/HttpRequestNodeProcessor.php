<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Engine\ExpressionEvaluator;
use Yahlox\Utils\InputSanitizer;
use Yahlox\Utils\RetryPolicy;
use RuntimeException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Performs HTTP requests with retry logic, timeouts, and input sanitization.
 *
 * Features:
 * - Timeout protection
 * - Retry logic with exponential backoff
 * - Input sanitization and validation
 * - Support for various HTTP methods
 * - Response caching and storage
 *
 * @package Yahlox
 */
final class HttpRequestNodeProcessor implements NodeProcessorInterface
{
    private const DEFAULT_TIMEOUT = 30;
    private const DEFAULT_CONNECT_TIMEOUT = 10;
    private const DEFAULT_MAX_RETRIES = 3;

    private ExpressionEvaluator $expressionEvaluator;
    private LoggerInterface $logger;
    private RetryPolicy $retryPolicy;

    /**
     * Construct HttpRequestNodeProcessor.
     *
     * @param ?ExpressionEvaluator $expressionEvaluator
     * @param ?LoggerInterface $logger
     * @param ?RetryPolicy $retryPolicy
     */
    public function __construct(
        ?ExpressionEvaluator $expressionEvaluator = null,
        ?LoggerInterface $logger = null,
        ?RetryPolicy $retryPolicy = null
    ) {
        $this->expressionEvaluator = $expressionEvaluator ?? new ExpressionEvaluator();
        $this->logger = $logger ?? new NullLogger();
        $this->retryPolicy = $retryPolicy ?? new RetryPolicy(
            maxAttempts: self::DEFAULT_MAX_RETRIES,
            initialDelayMs: 100,
            backoffMultiplier: 2.0
        );
    }

    /**
     * Execute HTTP request processor.
     *
     * @param Node $node
     * @param ExecutionContext $context
     * @return void
     * @throws RuntimeException
     */
    public function process(Node $node, ExecutionContext $context): void
    {
        $data = $node->data();
        $url = $data['url'] ?? null;
        $method = strtoupper($data['method'] ?? 'GET');
        $headers = $data['headers'] ?? [];
        $body = $data['body'] ?? null;
        $timeout = $data['timeout'] ?? self::DEFAULT_TIMEOUT;
        $connectTimeout = $data['connectTimeout'] ?? self::DEFAULT_CONNECT_TIMEOUT;
        $validateUrl = $data['validateUrl'] ?? true;
        $expectStatusCodes = $data['expectStatusCodes'] ?? [200, 201, 202];

        if (!$url) {
            throw new RuntimeException('HttpRequest node missing url');
        }

        // Resolve placeholders
        $resolvedUrl = $this->expressionEvaluator->evaluate($url, $context);
        $resolvedHeaders = [];
        foreach ($headers as $key => $value) {
            $resolvedHeaders[$key] = $this->expressionEvaluator->evaluate($value, $context);
        }
        $resolvedBody = $body ? $this->expressionEvaluator->evaluate($body, $context) : null;

        // Validate inputs
        if ($validateUrl) {
            try {
                $resolvedUrl = InputSanitizer::sanitize($resolvedUrl, 'url');
            } catch (RuntimeException $e) {
                throw new RuntimeException("Invalid URL in HttpRequest node: {$e->getMessage()}");
            }
        }

        // Execute with retry logic
        try {
            $response = $this->retryPolicy->execute(
                fn () => $this->executeRequest(
                    $resolvedUrl,
                    $method,
                    $resolvedHeaders,
                    $resolvedBody,
                    $timeout,
                    $connectTimeout
                ),
                fn ($attempt, $delay) => $this->logger->info(
                    "HTTP request retry attempt {$attempt}, delay {$delay}ms",
                    ['url' => $resolvedUrl]
                )
            );

            // Validate response status code
            $statusCode = $response['status_code'];
            if (!in_array($statusCode, $expectStatusCodes, true)) {
                $this->logger->warning(
                    "HTTP request returned unexpected status code: {$statusCode}",
                    ['url' => $resolvedUrl, 'expected' => $expectStatusCodes]
                );
            }

            // Store response
            $context->set("last_http_response", $response);

            if (isset($data['storeResponseAs'])) {
                $context->set($data['storeResponseAs'], $response['body']);
            }

            $this->logger->debug(
                'HTTP request completed successfully',
                ['url' => $resolvedUrl, 'statusCode' => $statusCode]
            );

        } catch (RuntimeException $e) {
            $this->logger->error(
                'HTTP request failed: ' . $e->getMessage(),
                ['url' => $resolvedUrl, 'method' => $method]
            );
            throw $e;
        }
    }

    /**
     * Execute the actual HTTP request.
     *
     * @param string $url
     * @param string $method
     * @param array $headers
     * @param ?string $body
     * @param int $timeout
     * @param int $connectTimeout
     * @return array Response array with 'body', 'status_code', 'headers'
     * @throws RuntimeException
     */
    private function executeRequest(
        string $url,
        string $method,
        array $headers,
        ?string $body,
        int $timeout,
        int $connectTimeout
    ): array {
        $ch = curl_init();

        try {
            // Set URL
            curl_setopt($ch, CURLOPT_URL, $url);

            // Set method
            if ($method !== 'GET' && $method !== 'POST') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            } elseif ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, 1);
            }

            // Set request body
            if ($body && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            // Set headers
            if (!empty($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $this->buildHeaders($headers));
            }

            // Set timeouts (critical for security)
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);

            // Security options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

            // Execute request
            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);

            if ($responseBody === false) {
                throw new RuntimeException(
                    "cURL error ({curl_errno($ch)}): {$curlError}"
                );
            }

            return [
                'url' => $url,
                'method' => $method,
                'status_code' => (int)$httpCode,
                'body' => $responseBody,
            ];

        } finally {
            curl_close($ch);
        }
    }

    /**
     * Build HTTP headers array.
     *
     * @param array $headers Key-value pairs
     * @return array Formatted headers
     */
    private function buildHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $key => $value) {
            $key = trim((string)$key);
            $value = trim((string)$value);

            if (!empty($key) && !empty($value)) {
                $result[] = "{$key}: {$value}";
            }
        }
        return $result;
    }

    /**
     * Set logger instance.
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Set retry policy.
     *
     * @param RetryPolicy $policy
     * @return void
     */
    public function setRetryPolicy(RetryPolicy $policy): void
    {
        $this->retryPolicy = $policy;
    }
}
