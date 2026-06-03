<?php

declare(strict_types=1);

namespace Yahlox\Engine;

use Yahlox\Domain\Workflow;
use Yahlox\Exceptions\InvalidWorkflowException;

final class WorkflowValidator
{
    public function validate(
        Workflow $workflow
    ): void {

        $startCount = 0;

        foreach ($workflow->nodes() as $node) {

            if ($node->type() === 'start') {
                $startCount++;
            }
        }

        if ($startCount === 0) {

            throw new InvalidWorkflowException(
                'Workflow must contain one start node.'
            );
        }

        if ($startCount > 1) {

            throw new InvalidWorkflowException(
                'Workflow contains multiple start nodes.'
            );
        }
    }
}