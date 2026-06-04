<?php

declare(strict_types=1);

namespace Yahlox\Storage;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for storage channel credentials and connection details.
 *
 * @package Yahlox
 */
final class StorageChannelCredential extends Model
{
    protected $table = 'storage_channel_credentials';

    protected $casts = [
        'connection_details' => 'array',
        'is_active' => 'boolean',
    ];

    protected $fillable = [
        'storage',
        'name',
        'provider',
        'connection_name',
        'connection_details',
        'is_active',
    ];
}
