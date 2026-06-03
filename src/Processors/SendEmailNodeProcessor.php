<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use RuntimeException;

final class SendEmailNodeProcessor implements NodeProcessorInterface
{
    public function process(Node $node, ExecutionContext $context): void
    {
        $data = $node->data();
        $to = $data['to'] ?? null;
        $subject = $data['subject'] ?? 'No subject';
        $body = $data['body'] ?? '';

        if (!$to) {
            throw new RuntimeException('SendEmail node missing "to" address');
        }

        $resolvedTo = $this->resolvePlaceholders($to, $context);
        $resolvedSubject = $this->resolvePlaceholders($subject, $context);
        $resolvedBody = $this->resolvePlaceholders($body, $context);

        $context->set("last_email_sent", [
            'to' => $resolvedTo,
            'subject' => $resolvedSubject,
            'body' => $resolvedBody,
        ]);
    }

    private function resolvePlaceholders(string $value, ExecutionContext $context): string
    {
        return preg_replace_callback('/\{([a-zA-Z0-9_.]+)\}/', function ($matches) use ($context) {
            return $context->get($matches[1]) ?? '';
        }, $value);
    }
}