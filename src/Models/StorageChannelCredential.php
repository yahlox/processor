<?php

declare(strict_types=1);

namespace Yahlox\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for storage channel credentials and connection details.
 *
 * @package Yahlox
 */
final class StorageChannelCredential extends Model
{
    protected $table = 'storage_channel_credentials';

    protected $fillable = [
        'storage',
        'name',
        'connection_name',
        'connection_details',
        'is_active',
    ];

    protected $casts = [
        'connection_details' => 'encrypted:array',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'connection_details',
    ];
}
