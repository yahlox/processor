<?php

declare(strict_types=1);

namespace Tests\Processors;

use PHPUnit\Framework\TestCase;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Processors\CreateRecordNodeProcessor;
use Yahlox\Processors\UpdateRecordNodeProcessor;
use Yahlox\Processors\DeleteRecordNodeProcessor;

final class CrudProcessorsTest extends TestCase
{
    public function testCreateRecord(): void
    {
        $context = new ExecutionContext();
        $node = new Node('c1', 'create_record', [
            'model' => 'User',
            'fields' => ['name' => 'John', 'email' => '{user_email}'],
            'storeAs' => 'new_user'
        ]);
        $context->set('user_email', 'john@example.com');

        (new CreateRecordNodeProcessor())->process($node, $context);

        $stored = $context->get('new_user');
        $this->assertSame('John', $stored['name']);
        $this->assertSame('john@example.com', $stored['email']);
        $this->assertNotNull($context->get('last_created_record'));
    }

    public function testCreateRecordStoresStoreAsId(): void
    {
        $context = new ExecutionContext();
        $node = new Node('c2', 'create_record', [
            'model' => 'User',
            'fields' => ['name' => 'John'],
            'storeAs' => 'new_user',
        ]);

        (new CreateRecordNodeProcessor())->process($node, $context);

        $created = $context->get('last_created_record');
        $this->assertSame($created['id'], $context->get('new_user_id'));
        $this->assertSame(['name' => 'John'], $context->get('new_user'));
    }

    public function testUpdateRecord(): void
    {
        $context = new ExecutionContext();
        $node = new Node('u1', 'update_record', [
            'record_id' => 'rec_123',
            'fields' => ['name' => 'Jane']
        ]);

        (new UpdateRecordNodeProcessor())->process($node, $context);
        $this->assertSame(['name' => 'Jane'], $context->get("updated_record_rec_123"));
    }

    public function testDeleteRecord(): void
    {
        $context = new ExecutionContext();
        $node = new Node('d1', 'delete_record', ['record_id' => 'rec_456']);

        (new DeleteRecordNodeProcessor())->process($node, $context);
        $this->assertTrue($context->get('deleted_record_rec_456'));
        $this->assertSame('rec_456', $context->get('last_deleted_record_id'));
    }
}