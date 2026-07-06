<?php

namespace App\Services\Sync;

use App\Models\SyncQueueItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Applies an incoming wire record to the local database.
 *
 * Conflict rule (recorded in the README):
 *  - New uuid → create.
 *  - Local copy has no unsynced changes → incoming wins (it is by
 *    definition newer than the last agreed state).
 *  - Both sides changed:
 *      * any conflict-sensitive field differs → quarantine the incoming
 *        record in sync_queue (status "conflict") for manual HR review;
 *        nothing is applied.
 *      * otherwise last-write-wins by updated_at, whole record.
 */
class SyncApplier
{
    public const APPLIED = 'applied';

    public const SKIPPED_OLDER = 'skipped_older';

    public const CONFLICT = 'conflict';

    public function apply(array $record, ?string $device = null): string
    {
        $modelClass = SyncRegistry::modelFor($record['type']);
        $attributes = $this->resolveForeignKeys($record['type'], $record['attributes']);

        // Never trust the wire: drop anything that isn't a sync-writable
        // column of this type (ids, user FKs, unknown keys).
        $attributes = array_intersect_key(
            $attributes,
            array_flip(SyncRegistry::writableColumns($record['type'])),
        );

        /** @var Model|null $local */
        $local = $modelClass::withoutGlobalScopes()
            ->when(
                method_exists($modelClass, 'bootSoftDeletes'),
                fn ($query) => $query->withTrashed(),
            )
            ->where('uuid', $record['uuid'])
            ->first();

        if ($local === null) {
            return $this->applyInsert($modelClass, $record, $attributes);
        }

        $localChanged = $local->synced_at === null;
        $incomingAt = Carbon::parse($record['updated_at']);

        if ($localChanged) {
            if ($this->hasSensitiveConflict($record['type'], $local, $attributes)) {
                $this->quarantine($record, $device);

                return self::CONFLICT;
            }

            if ($incomingAt->lte($local->updated_at)) {
                // Local copy is newer: keep it; it will win on its own push.
                return self::SKIPPED_OLDER;
            }
        }

        return $this->applyUpdate($local, $record, $attributes);
    }

    private function applyInsert(string $modelClass, array $record, array $attributes): string
    {
        $modelClass::withoutEvents(function () use ($modelClass, $record, $attributes) {
            $model = new $modelClass;
            $model->setRawAttributes($attributes + ['uuid' => $record['uuid']]);
            $model->synced_at = now();
            $model->saveQuietly();
        });

        return self::APPLIED;
    }

    private function applyUpdate(Model $local, array $record, array $attributes): string
    {
        $local::withoutEvents(function () use ($local, $attributes) {
            $local->setRawAttributes($attributes + [
                'id' => $local->id,
                'uuid' => $local->uuid,
            ]);
            $local->synced_at = now();
            $local->saveQuietly();
        });

        return self::APPLIED;
    }

    private function resolveForeignKeys(string $type, array $attributes): array
    {
        foreach (SyncRegistry::UUID_FOREIGN_KEYS[$type] ?? [] as $column => [$wireKey, $targetType]) {
            $uuid = $attributes[$wireKey] ?? null;
            unset($attributes[$wireKey]);

            if ($uuid === null) {
                $attributes[$column] = null;

                continue;
            }

            $localId = SyncRegistry::modelFor($targetType)::where('uuid', $uuid)->value('id');

            if ($localId === null) {
                throw new \RuntimeException(
                    "Sync dependency missing: {$type} references {$targetType} {$uuid} which does not exist locally."
                );
            }

            $attributes[$column] = $localId;
        }

        return $attributes;
    }

    private function hasSensitiveConflict(string $type, Model $local, array $incoming): bool
    {
        foreach (SyncRegistry::CONFLICT_SENSITIVE[$type] ?? [] as $field) {
            if (! array_key_exists($field, $incoming)) {
                continue;
            }

            if ((string) $local->getRawOriginal($field) !== (string) $incoming[$field]) {
                return true;
            }
        }

        return false;
    }

    private function quarantine(array $record, ?string $device): void
    {
        SyncQueueItem::updateOrCreate(
            [
                'record_type' => $record['type'],
                'record_uuid' => $record['uuid'],
                'status' => 'conflict',
            ],
            [
                'operation' => 'update',
                'payload' => $record,
                'device_name' => $device,
            ],
        );

        DB::table('sync_log')->updateOrInsert(
            ['branch_id' => null, 'direction' => 'pull'],
            ['last_conflict_at' => now(), 'updated_at' => now(), 'created_at' => now()],
        );
    }
}
