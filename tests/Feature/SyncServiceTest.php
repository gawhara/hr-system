<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\SyncQueueItem;
use App\Services\Sync\SyncApplier;
use App\Services\Sync\SyncRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class SyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(array $attributes = []): Employee
    {
        $company = Company::firstOr(fn () => Company::create(['name_ar' => 'شركة', 'name_en' => 'Co']));

        return Employee::create($attributes + [
            'company_id' => $company->id,
            'name_ar' => 'موظف مزامنة',
            'basic_salary' => 5000,
        ]);
    }

    public function test_serialization_translates_local_fks_to_uuids(): void
    {
        $employee = $this->makeEmployee();
        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $payload = SyncRegistry::serialize($record);

        $this->assertSame('attendance_record', $payload['type']);
        $this->assertSame($record->uuid, $payload['uuid']);
        $this->assertArrayNotHasKey('id', $payload['attributes']);
        $this->assertArrayNotHasKey('employee_id', $payload['attributes']);
        $this->assertSame($employee->uuid, $payload['attributes']['employee_uuid']);
    }

    public function test_applier_creates_new_records_resolving_uuid_fks(): void
    {
        $employee = $this->makeEmployee();
        $applier = app(SyncApplier::class);

        $incomingUuid = (string) Str::uuid();
        $status = $applier->apply([
            'type' => 'attendance_record',
            'uuid' => $incomingUuid,
            'updated_at' => now()->toIso8601String(),
            'attributes' => [
                'employee_uuid' => $employee->uuid,
                'work_date' => now()->toDateString(),
                'status' => 'present',
                'late_minutes' => 0,
                'absence_minutes' => 0,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
        ]);

        $this->assertSame(SyncApplier::APPLIED, $status);

        $created = AttendanceRecord::where('uuid', $incomingUuid)->first();
        $this->assertNotNull($created);
        $this->assertSame($employee->id, $created->employee_id);
        $this->assertNotNull($created->synced_at);
    }

    public function test_sensitive_conflict_is_quarantined_not_applied(): void
    {
        $employee = $this->makeEmployee(['basic_salary' => 5000]);
        // Local record has unsynced changes (synced_at null after create).
        $this->assertNull($employee->synced_at);

        $incoming = SyncRegistry::serialize($employee->fresh());
        $incoming['attributes']['basic_salary'] = '9000.00'; // remote salary change
        $incoming['updated_at'] = now()->addMinute()->toIso8601String();

        $status = app(SyncApplier::class)->apply($incoming, 'branch-2');

        $this->assertSame(SyncApplier::CONFLICT, $status);
        $this->assertEquals(5000, (float) $employee->fresh()->basic_salary);

        $conflict = SyncQueueItem::where('record_uuid', $employee->uuid)->where('status', 'conflict')->first();
        $this->assertNotNull($conflict);
        $this->assertSame('branch-2', $conflict->device_name);
    }

    public function test_non_sensitive_change_applies_with_last_write_wins(): void
    {
        $employee = $this->makeEmployee();
        $employee->markSynced(); // no local unsynced changes

        $incoming = SyncRegistry::serialize($employee->fresh());
        $incoming['attributes']['phone_2'] = '0555555555';
        $incoming['updated_at'] = now()->addMinute()->toIso8601String();

        $status = app(SyncApplier::class)->apply($incoming);

        $this->assertSame(SyncApplier::APPLIED, $status);
        $this->assertSame('0555555555', $employee->fresh()->phone_2);
    }

    public function test_push_endpoint_requires_token_and_central_role(): void
    {
        config(['hr.sync.token' => 'secret-token', 'hr.sync.role' => 'central']);

        $employee = $this->makeEmployee();
        $payload = ['device' => 'branch-1', 'records' => [SyncRegistry::serialize($employee)]];

        $this->postJson('/api/sync/push', $payload)->assertUnauthorized();

        $this->withToken('wrong')->postJson('/api/sync/push', $payload)->assertUnauthorized();

        config(['hr.sync.role' => 'branch']);
        $this->withToken('secret-token')->postJson('/api/sync/push', $payload)->assertForbidden();

        config(['hr.sync.role' => 'central']);
        $this->withToken('secret-token')
            ->postJson('/api/sync/push', $payload)
            ->assertOk()
            ->assertJsonPath('results.0.status', SyncApplier::SKIPPED_OLDER);
    }

    public function test_pull_endpoint_returns_records_since_cursor(): void
    {
        config(['hr.sync.token' => 'secret-token', 'hr.sync.role' => 'central']);

        $this->makeEmployee();

        $response = $this->withToken('secret-token')->getJson('/api/sync/pull');

        $response->assertOk();
        $this->assertNotEmpty($response->json('records'));

        $future = now()->addHour()->toIso8601String();
        $this->withToken('secret-token')
            ->getJson('/api/sync/pull?since=' . urlencode($future))
            ->assertOk()
            ->assertJsonCount(0, 'records');
    }

    public function test_branch_sync_command_pushes_and_marks_synced(): void
    {
        config([
            'hr.sync.role' => 'branch',
            'hr.sync.central_url' => 'https://central.test',
            'hr.sync.token' => 'secret-token',
        ]);

        $employee = $this->makeEmployee();

        Http::fake([
            'central.test/api/sync/push' => Http::response([
                'results' => SyncRegistry::MODELS === [] ? [] : [
                    ['type' => 'employee', 'uuid' => $employee->uuid, 'status' => SyncApplier::APPLIED],
                ],
            ]),
            'central.test/api/sync/pull*' => Http::response([
                'server_time' => now()->toIso8601String(),
                'records' => [],
            ]),
        ]);

        $this->artisan('hr:sync')->assertSuccessful();

        $this->assertNotNull($employee->fresh()->synced_at);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/sync/push')
                && $request->hasHeader('Authorization', 'Bearer secret-token');
        });
    }

    public function test_sync_command_refuses_to_run_on_non_branch_nodes(): void
    {
        config(['hr.sync.role' => 'standalone']);

        $this->artisan('hr:sync')->assertFailed();
    }
}
