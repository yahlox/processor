<?php

declare(strict_types=1);

namespace Tests\Processors;

use PHPUnit\Framework\TestCase;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Processors\ReadRecordNodeProcessor;

final class ReadRecordNodeProcessorTest extends TestCase
{
    public function testReadRecordWithoutRecordIdReturnsAllRecordsArray(): void
    {
        $context = new ExecutionContext();
        $node = new Node('read_all', 'readRecord', [
            'model' => 'GenericRecord',
            'storeAs' => 'records',
        ]);

        (new ReadRecordNodeProcessor())->process($node, $context);

        $this->assertSame([], $context->get('records'));
        $this->assertSame(['id' => null, 'data' => []], $context->get('last_read_record'));
    }

    public function testReadRecordWithQueryMetadataDoesNotRequireRecordId(): void
    {
        $context = new ExecutionContext();
        $node = new Node('read_query', 'readRecord', [
            'model' => 'GenericRecord',
            'query' => [
                ['status', 'pending'],
            ],
            'storeAs' => 'query_results',
        ]);

        (new ReadRecordNodeProcessor())->process($node, $context);

        $this->assertSame([], $context->get('query_results'));
        $this->assertSame(['id' => null, 'data' => []], $context->get('last_read_record'));
    }
}
