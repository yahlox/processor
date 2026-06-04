<?php

declare(strict_types=1);

namespace Tests\Processors;

use PHPUnit\Framework\TestCase;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Processors\SendEmailNodeProcessor;
use Yahlox\Processors\SendSmsNodeProcessor;
use Yahlox\Processors\SendNotificationNodeProcessor;

final class CommunicationProcessorsTest extends TestCase
{
    public function testSendEmail(): void
    {
        $context = new ExecutionContext();
        $node = new Node('e1', 'send_email', [
            'to' => 'user@example.com',
            'subject' => 'Welcome {name}',
            'body' => 'Hello {name}'
        ]);
        $context->set('name', 'John');

        (new SendEmailNodeProcessor())->process($node, $context);
        $email = $context->get('last_email_sent');
        $this->assertSame('user@example.com', $email['to']);
        $this->assertSame('Welcome John', $email['subject']);
        $this->assertSame('Hello John', $email['body']);
    }

    public function testSendSms(): void
    {
        $context = new ExecutionContext();
        $node = new Node('s1', 'send_sms', [
            'to' => '+123456789',
            'message' => 'Your code is {code}'
        ]);
        $context->set('code', 123456);

        (new SendSmsNodeProcessor())->process($node, $context);
        $sms = $context->get('last_sms_sent');
        $this->assertSame('+123456789', $sms['to']);
        $this->assertSame('Your code is 123456', $sms['message']);
    }

    public function testSendNotification(): void
    {
        $context = new ExecutionContext();
        $node = new Node('n1', 'send_notification', [
            'user_id' => 'user_789',
            'title' => 'New message',
            'body' => 'You have {count} messages'
        ]);
        $context->set('count', 5);

        (new SendNotificationNodeProcessor())->process($node, $context);
        $notif = $context->get('last_notification_sent');
        $this->assertSame('user_789', $notif['to']);
        $this->assertSame('You have 5 messages', $notif['body']);
    }
}