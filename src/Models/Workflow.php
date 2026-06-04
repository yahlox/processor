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
        'name',
        'description',
        'definition',
        'is_active',
    ];

    protected $casts = [
        'definition' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Workflow $workflow): void {
            if (empty($workflow->node)) {
                do {
                    $node = Str::upper(Str::random(36));
                } while (
                    self::where('node', $node)->exists()
                );

                $workflow->node = $node;
            }
        });
    }
}
