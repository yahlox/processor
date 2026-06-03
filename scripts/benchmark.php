<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Yahlox\Parser\ReactFlowParser;
use Yahlox\Registry\NodeProcessorRegistry;
use Yahlox\Engine\WorkflowValidator;
use Yahlox\Engine\WorkflowExecutor;
use Yahlox\Processors\StartNodeProcessor;
use Yahlox\Processors\EndNodeProcessor;
use Yahlox\YahloxLibrary;

$iterations = (int) ($argv[1] ?? 20000);
$warmup = (int) ($argv[2] ?? 1000);

$registry = new NodeProcessorRegistry();
$registry->register('start', new StartNodeProcessor());
$registry->register('end', new EndNodeProcessor());

$parser = new ReactFlowParser();
$validator = new WorkflowValidator();
$executor = new WorkflowExecutor($registry, $validator);
$yahlox = new YahloxLibrary($parser, $executor);

$json = [
    'nodes' => [
        ['id' => 'start', 'type' => 'start'],
        ['id' => 'end', 'type' => 'end'],
    ],
    'edges' => [
        ['source' => 'start', 'target' => 'end'],
    ],
];

echo "Yahlox benchmark starting — iterations={$iterations} warmup={$warmup}\n";

// Warmup parse-only
for ($i = 0; $i < $warmup; $i++) {
    $parser->parse($json);
}

$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $parser->parse($json);
}
$parseTime = microtime(true) - $start;
$parseOps = $parseTime > 0 ? $iterations / $parseTime : INF;

// Warmup full run
for ($i = 0; $i < $warmup; $i++) {
    $yahlox->run($json, new \Yahlox\Domain\ExecutionContext());
}

$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $yahlox->run($json, new \Yahlox\Domain\ExecutionContext());
}
$runTime = microtime(true) - $start;
$runOps = $runTime > 0 ? $iterations / $runTime : INF;

echo sprintf("Parse-only: %.2f ops/sec (total %.4fs)\n", $parseOps, $parseTime);
echo sprintf("Parse+Execute: %.2f ops/sec (total %.4fs)\n", $runOps, $runTime);

echo "Benchmark complete.\n";
