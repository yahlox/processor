<?php

declare(strict_types=1);

namespace Yahlox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Represents a parsed workflow graph of nodes and edges.
 *
 * @package Yahlox
 */
final class Workflow extends Model
{
    protected $table = 'workflows';

    protected $fillable = [
        'node',
        'name',
        'description',
        'nodes',
        'edges',
        'is_active',
    ];

    protected $casts = [
        'nodes' => 'array',
        'edges' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Workflow $workflow): void {
            if (empty($workflow->node)) {
                do {
                    /** Uniquely generated node ID */
                    $workflow->node = Str::random(36);
                } while (
                    static::where('node', $workflow->node)->exists()
                );
            }
        });
    }
}