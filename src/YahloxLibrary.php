<?php

declare(strict_types=1);

namespace Yahlox;

use Yahlox\Domain\Workflow;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Parser\ReactFlowParser;
use Yahlox\Engine\WorkflowExecutor;

final class YahloxLibrary
{
    public function __construct(
        private readonly ReactFlowParser $parser,
        private readonly WorkflowExecutor $executor
    ) {
    }

    public function run(
        array $json,
        ExecutionContext $context
    ): void {

        $workflow = $this->parser->parse(
            $json
        );

        $this->executor->execute(
            $workflow,
            $context
        );
    }

    public function parse(
        array $json
    ): Workflow {

        return $this->parser->parse(
            $json
        );
    }
}