<?php

declare(strict_types=1);

namespace Yahlox\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for persisted workflow execution metadata.
 *
 * @package Yahlox
 */
final class WorkflowExecution extends Model
{
    protected $table = 'workflow_executions';

    protected $fillable = [
        'workflow_id',
        'status',
        'execution_context',
        'started_at',
        'completed_at',
        'error',
    ];

    protected $casts = [
        'execution_context' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'error' => 'array',
    ];
}
