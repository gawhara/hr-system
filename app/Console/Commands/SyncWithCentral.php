<?php

namespace App\Console\Commands;

use App\Services\Sync\SyncApplier;
use App\Services\Sync\SyncRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Branch-node sync (Strategy A): push unsynced local records to the central
 * server, then pull records changed centrally since the last pull. Run on a
 * schedule or manually on reconnect; every call is safe to repeat.
 */
class SyncWithCentral extends Command
{
    protected $signature = 'hr:sync {--push-only} {--pull-only}';

    protected $description = 'Push local changes to the central server and pull remote changes (branch nodes)';

    public function handle(SyncApplier $applier): int
    {
        if (config('hr.sync.role') !== 'branch') {
            $this->error('hr:sync only runs on branch nodes (HR_SYNC_ROLE=branch).');

            return self::FAILURE;
        }

        $baseUrl = rtrim((string) config('hr.sync.central_url'), '/');
        $token = (string) config('hr.sync.token');

        if ($baseUrl === '' || $token === '') {
            $this->error('HR_SYNC_CENTRAL_URL and HR_SYNC_TOKEN must be configured.');

            return self::FAILURE;
        }

        if (! $this->option('pull-only')) {
            $this->push($baseUrl, $token);
        }

        if (! $this->option('push-only')) {
            $this->pull($baseUrl, $token, $applier);
        }

        $this->updatePendingCounts();

        return self::SUCCESS;
    }

    private function push(string $baseUrl, string $token): void
    {
        $pushed = 0;
        $conflicts = 0;

        foreach (SyncRegistry::MODELS as $type => $modelClass) {
            $modelClass::query()
                ->when(
                    method_exists($modelClass, 'bootSoftDeletes'),
                    fn ($builder) => $builder->withTrashed(),
                )
                ->whereNull('synced_at')
                ->chunkById(100, function ($models) use ($baseUrl, $token, &$pushed, &$conflicts) {
                    $records = $models->map(fn ($model) => SyncRegistry::serialize($model))->all();

                    $response = Http::withToken($token)
                        ->acceptJson()
                        ->post("{$baseUrl}/api/sync/push", [
                            'device' => config('hr.sync.device_name'),
                            'records' => $records,
                        ])
                        ->throw();

                    $statuses = collect($response->json('results'))->keyBy('uuid');

                    foreach ($models as $model) {
                        $status = $statuses[$model->uuid]['status'] ?? 'error';

                        if (in_array($status, [SyncApplier::APPLIED, SyncApplier::SKIPPED_OLDER], true)) {
                            $model->markSynced();
                            $pushed++;
                        } elseif ($status === SyncApplier::CONFLICT) {
                            $conflicts++;
                        }
                    }
                });
        }

        DB::table('sync_log')->updateOrInsert(
            ['branch_id' => null, 'direction' => 'push'],
            ['last_synced_at' => now(), 'updated_at' => now(), 'created_at' => now()]
                + ($conflicts > 0 ? ['last_conflict_at' => now()] : []),
        );

        $this->info("Pushed: {$pushed}, conflicts flagged centrally: {$conflicts}");
    }

    private function pull(string $baseUrl, string $token, SyncApplier $applier): void
    {
        $since = DB::table('sync_log')
            ->where('branch_id', null)
            ->where('direction', 'pull')
            ->value('last_synced_at');

        $response = Http::withToken($token)
            ->acceptJson()
            ->get("{$baseUrl}/api/sync/pull", array_filter(['since' => $since]))
            ->throw();

        $applied = 0;
        $conflicts = 0;
        $skipped = 0;

        foreach ($response->json('records', []) as $record) {
            $status = $applier->apply($record, 'central');

            match ($status) {
                SyncApplier::APPLIED => $applied++,
                SyncApplier::CONFLICT => $conflicts++,
                default => $skipped++,
            };
        }

        DB::table('sync_log')->updateOrInsert(
            ['branch_id' => null, 'direction' => 'pull'],
            [
                'last_synced_at' => $response->json('server_time') ?? now(),
                'updated_at' => now(),
                'created_at' => now(),
            ] + ($conflicts > 0 ? ['last_conflict_at' => now()] : []),
        );

        $this->info("Pulled: {$applied} applied, {$skipped} skipped, {$conflicts} conflicts for review");
    }

    private function updatePendingCounts(): void
    {
        $pending = 0;

        foreach (SyncRegistry::MODELS as $modelClass) {
            $pending += $modelClass::query()
                ->when(
                    method_exists($modelClass, 'bootSoftDeletes'),
                    fn ($builder) => $builder->withTrashed(),
                )
                ->whereNull('synced_at')
                ->count();
        }

        DB::table('sync_log')
            ->where('branch_id', null)
            ->where('direction', 'push')
            ->update(['pending_push_count' => $pending]);
    }
}
