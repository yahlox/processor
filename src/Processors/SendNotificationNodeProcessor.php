<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use RuntimeException;

final class SendNotificationNodeProcessor implements NodeProcessorInterface
{
    public function process(Node $node, ExecutionContext $context): void
    {
        $data = $node->data();
        $userId = $data['user_id'] ?? null;
        $title = $data['title'] ?? '';
        $body = $data['body'] ?? '';

        if (!$userId) {
            throw new RuntimeException('SendNotification node missing user_id');
        }

        $resolvedUserId = $this->resolvePlaceholders($userId, $context);
        $resolvedTitle = $this->resolvePlaceholders($title, $context);
        $resolvedBody = $this->resolvePlaceholders($body, $context);

        $context->set("last_notification_sent", [
            'user_id' => $resolvedUserId,
            'title' => $resolvedTitle,
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