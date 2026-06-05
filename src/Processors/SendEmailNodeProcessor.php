<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Engine\ExpressionEvaluator;
use Yahlox\Send\SendChannelStrategyManager;
use Yahlox\Utils\InputSanitizer;
use RuntimeException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Sends email messages through configured send channel strategies.
 *
 * Features:
 * - Input validation and sanitization
 * - Safe placeholder resolution
 * - Email address validation
 * - Configurable send channels
 * - Comprehensive logging
 *
 * @package Yahlox
 */
final class SendEmailNodeProcessor implements NodeProcessorInterface
{
    private SendChannelStrategyManager $channelManager;
    private ExpressionEvaluator $expressionEvaluator;
    private LoggerInterface $logger;

    /**
     * Construct SendEmailNodeProcessor.
     *
     * @param ?SendChannelStrategyManager $channelManager
     * @param ?ExpressionEvaluator $expressionEvaluator
     * @param ?LoggerInterface $logger
     */
    public function __construct(
        ?SendChannelStrategyManager $channelManager = null,
        ?ExpressionEvaluator $expressionEvaluator = null,
        ?LoggerInterface $logger = null
    ) {
        $this->channelManager = $channelManager ?? SendChannelStrategyManager::createDefault();
        $this->expressionEvaluator = $expressionEvaluator ?? new ExpressionEvaluator();
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Process send email node.
     *
     * @param Node $node
     * @param ExecutionContext $context
     * @return void
     * @throws RuntimeException
     */
    public function process(Node $node, ExecutionContext $context): void
    {
        $data = $node->data();
        $to = $data['to'] ?? null;
        $subject = $data['subject'] ?? 'No subject';
        $body = $data['body'] ?? '';
        $channel = $data['channel'] ?? 'email';
        $validateEmail = $data['validateEmail'] ?? true;
        $htmlContent = $data['htmlContent'] ?? false;

        if (!$to) {
            throw new RuntimeException('SendEmail node missing "to" address');
        }

        try {
            // Resolve placeholders
            $resolvedTo = $this->expressionEvaluator->evaluate($to, $context);
            $resolvedSubject = $this->expressionEvaluator->evaluate($subject, $context);
            $resolvedBody = $this->expressionEvaluator->evaluate($body, $context);

            // Sanitize and validate email
            if ($validateEmail) {
                $resolvedTo = InputSanitizer::sanitize($resolvedTo, 'email');
            }

            // Sanitize subject and body
            if ($htmlContent) {
                // Allow HTML in body but sanitize
                $resolvedBody = $this->sanitizeHtml($resolvedBody);
            } else {
                // For plain text, escape HTML
                $resolvedBody = InputSanitizer::sanitize($resolvedBody, 'html');
            }

            $resolvedSubject = InputSanitizer::sanitize($resolvedSubject, 'string');

            $payload = [
                'to' => $resolvedTo,
                'subject' => $resolvedSubject,
                'body' => $resolvedBody,
                'htmlContent' => $htmlContent,
            ];

            // Send via configured channel
            $strategy = $this->channelManager->resolve(['channel' => $channel]);
            $result = $strategy->send($payload, $context, $data['config'] ?? []);

            // Store result
            $context->set('last_email_sent', $payload);
            $context->set('last_send_result', $result);

            $this->logger->info(
                'Email sent successfully',
                ['to' => $resolvedTo, 'channel' => $channel]
            );

        } catch (RuntimeException $e) {
            $this->logger->error(
                'Email sending failed: ' . $e->getMessage(),
                ['to' => $data['to'] ?? 'unknown']
            );
            throw $e;
        }
    }

    /**
     * Sanitize HTML content to prevent XSS.
     *
     * @param string $html
     * @return string
     */
    private function sanitizeHtml(string $html): string
    {
        // Allow basic HTML tags but strip dangerous ones
        $allowed_tags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><blockquote>';
        $sanitized = strip_tags($html, $allowed_tags);

        // Remove event handlers
        $sanitized = preg_replace('/on\w+\s*=\s*["\']?[^"\']*["\']?/i', '', $sanitized);

        return $sanitized ?? $html;
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
}
