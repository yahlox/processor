<?php

declare(strict_types=1);

namespace Yahlox\Models;

use Illuminate\Database\Eloquent\Model;

final class SendChannelCredential extends Model
{
    protected $table = 'send_channel_credentials';

    protected $fillable = [
        'channel',
        'provider',
        'name',
        'credentials',
        'is_active',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'credentials',
    ];
}
