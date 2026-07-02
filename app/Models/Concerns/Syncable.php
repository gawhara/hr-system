<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Offline Strategy A sync identity: records created on any branch database
 * carry a UUID so they merge into the central server without id collisions.
 * `synced_at` stays null until the sync service confirms the push.
 */
trait Syncable
{
    public static function bootSyncable(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        // Any local change invalidates the previous sync confirmation.
        static::updating(function ($model) {
            if (! $model->isDirty('synced_at')) {
                $model->synced_at = null;
            }
        });
    }

    public function scopeUnsynced($query)
    {
        return $query->whereNull('synced_at');
    }

    public function markSynced(): void
    {
        $this->forceFill(['synced_at' => now()])->saveQuietly();
    }
}
