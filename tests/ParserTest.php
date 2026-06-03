<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Yahlox\Parser\ReactFlowParser;

final class ParserTest extends TestCase
{
    public function test_parse_reactflow_json()
    {
        $json = [
            'nodes' => [
                [
                    'id' => '1',
                    'type' => 'start',
                ],
            ],
            'edges' => [],
        ];

        $workflow = (
            new ReactFlowParser()
        )->parse($json);

        $this->assertCount(
            1,
            $workflow->nodes()
        );

        $this->assertEquals(
            'start',
            $workflow->nodes()[0]->type()
        );
    }
}