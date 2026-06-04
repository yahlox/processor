<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Send\SendChannelStrategyManager;
use RuntimeException;

final class SendEmailNodeProcessor implements NodeProcessorInterface
{
    private SendChannelStrategyManager $channelManager;

    public function __construct(?SendChannelStrategyManager $channelManager = null)
    {
        $this->channelManager = $channelManager ?? SendChannelStrategyManager::createDefault();
    }

    public function process(Node $node, ExecutionContext $context): void
    {
        $data = $node->data();
        $to = $data['to'] ?? null;
        $subject = $data['subject'] ?? 'No subject';
        $body = $data['body'] ?? '';
        $channel = $data['channel'] ?? 'email';

        if (!$to) {
            throw new RuntimeException('SendEmail node missing "to" address');
        }

        $payload = [
            'to' => $this->resolvePlaceholders($to, $context),
            'subject' => $this->resolvePlaceholders($subject, $context),
            'body' => $this->resolvePlaceholders($body, $context),
        ];

        $strategy = $this->channelManager->resolve(['channel' => $channel]);
        $result = $strategy->send($payload, $context, $data['config'] ?? []);

        $context->set('last_email_sent', $payload);
        $context->set('last_send_result', $result);
    }

    private function resolvePlaceholders(string $value, ExecutionContext $context): string
    {
        return preg_replace_callback('/\{([a-zA-Z0-9_.]+)\}/', function ($matches) use ($context) {
            return $context->get($matches[1]) ?? '';
        }, $value);
    }
}
