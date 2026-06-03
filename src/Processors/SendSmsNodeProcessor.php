<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use RuntimeException;

final class SendSmsNodeProcessor implements NodeProcessorInterface
{
    public function process(Node $node, ExecutionContext $context): void
    {
        $data = $node->data();
        $to = $data['to'] ?? null;
        $message = $data['message'] ?? '';

        if (!$to) {
            throw new RuntimeException('SendSms node missing "to" number');
        }

        $resolvedTo = $this->resolvePlaceholders($to, $context);
        $resolvedMessage = $this->resolvePlaceholders($message, $context);

        $context->set("last_sms_sent", [
            'to' => $resolvedTo,
            'message' => $resolvedMessage,
        ]);
    }

    private function resolvePlaceholders(string $value, ExecutionContext $context): string
    {
        return preg_replace_callback('/\{([a-zA-Z0-9_.]+)\}/', function ($matches) use ($context) {
            return $context->get($matches[1]) ?? '';
        }, $value);
    }
}