<?php

declare(strict_types=1);

namespace Yahlox\Send;

use Yahlox\Contracts\SendChannelStrategyInterface;
use Yahlox\Domain\ExecutionContext;
use RuntimeException;

final class SendChannelStrategyManager
{
    private array $channels = [];
    private string $defaultChannel;

    public function __construct(array $channels = [], string $defaultChannel = 'log')
    {
        foreach ($channels as $name => $strategy) {
            $this->register($name, $strategy);
        }

        $this->defaultChannel = $defaultChannel;
    }

    public static function createDefault(): self
    {
        return new self([
            'log' => new LogSendChannelStrategy(),
            'email' => new EmailSendChannelStrategy(),
            'sms' => new SmsSendChannelStrategy(),
            'viber' => new ViberSendChannelStrategy(),
            'whatsapp' => new WhatsAppSendChannelStrategy(),
            'messenger' => new MessengerSendChannelStrategy(),
            'telegram' => new TelegramSendChannelStrategy(),
        ], 'log');
    }

    public function register(string $name, SendChannelStrategyInterface $strategy): void
    {
        $this->channels[$name] = $strategy;
    }

    public function get(string $name): SendChannelStrategyInterface
    {
        if (!isset($this->channels[$name])) {
            throw new RuntimeException(sprintf('Send channel strategy [%s] not found.', $name));
        }

        return $this->channels[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->channels[$name]);
    }

    public function resolve(array $data): SendChannelStrategyInterface
    {
        $channel = $data['channel'] ?? $this->defaultChannel;

        if ($this->has($channel)) {
            return $this->get($channel);
        }

        return $this->get($this->defaultChannel);
    }
}
