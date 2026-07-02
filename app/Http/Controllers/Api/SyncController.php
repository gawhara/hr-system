<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Sync\SyncApplier;
use App\Services\Sync\SyncRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SyncController extends Controller
{
    /**
     * Branch → central: apply pushed records, reporting per-record status so
     * the branch only marks confirmed records as synced.
     */
    public function push(Request $request, SyncApplier $applier)
    {
        $data = $request->validate([
            'device' => ['nullable', 'string', 'max:255'],
            'records' => ['required', 'array', 'max:500'],
            'records.*.type' => ['required', 'string', 'in:' . implode(',', array_keys(SyncRegistry::MODELS))],
            'records.*.uuid' => ['required', 'uuid'],
            'records.*.updated_at' => ['required', 'date'],
            'records.*.attributes' => ['required', 'array'],
        ]);

        $results = [];

        foreach ($data['records'] as $record) {
            try {
                $status = $applier->apply($record, $data['device'] ?? null);
            } catch (\RuntimeException $exception) {
                $status = 'error';
            }

            $results[] = [
                'type' => $record['type'],
                'uuid' => $record['uuid'],
                'status' => $status,
            ];
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Central → branch: every synced record changed since the given cursor.
     */
    public function pull(Request $request)
    {
        $data = $request->validate([
            'since' => ['nullable', 'date'],
        ]);

        $since = isset($data['since']) ? Carbon::parse($data['since']) : null;
        $records = [];

        foreach (SyncRegistry::MODELS as $type => $modelClass) {
            $query = $modelClass::query()
                ->when(
                    method_exists($modelClass, 'bootSoftDeletes'),
                    fn ($builder) => $builder->withTrashed(),
                )
                ->when($since, fn ($builder) => $builder->where('updated_at', '>', $since))
                ->orderBy('updated_at')
                ->limit(500);

            foreach ($query->get() as $model) {
                $records[] = SyncRegistry::serialize($model);
            }
        }

        return response()->json([
            'server_time' => now()->toIso8601String(),
            'records' => $records,
        ]);
    }
}
