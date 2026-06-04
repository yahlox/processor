<?php

declare(strict_types=1);

namespace Yahlox\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a parsed workflow graph of nodes and edges.
 *
 * @package Yahlox
 */
final class Workflow extends Model
{
    protected $table = 'workflows';

    protected $fillable = [
        'name',
        'description',
        'definition',
        'is_active',
    ];

    protected $casts = [
        'definition' => 'array',
        'is_active' => 'boolean',
    ];
}
