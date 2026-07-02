<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncQueueItem extends Model
{
    protected $table = 'sync_queue';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'synced_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
