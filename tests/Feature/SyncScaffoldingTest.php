<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncScaffoldingTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_writable_records_receive_a_sync_uuid_on_create(): void
    {
        $company = Company::create([
            'name_ar' => 'شركة اختبار',
            'name_en' => 'Test Co',
        ]);

        $employee = Employee::create([
            'company_id' => $company->id,
            'name_ar' => 'موظف مزامنة',
        ]);

        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $this->assertTrue(\Illuminate\Support\Str::isUuid($employee->uuid));
        $this->assertTrue(\Illuminate\Support\Str::isUuid($record->uuid));
        $this->assertNull($employee->synced_at);
    }

    public function test_local_edits_invalidate_previous_sync_confirmation(): void
    {
        $company = Company::create([
            'name_ar' => 'شركة اختبار',
            'name_en' => 'Test Co',
        ]);

        $employee = Employee::create([
            'company_id' => $company->id,
            'name_ar' => 'موظف مزامنة',
        ]);

        $employee->markSynced();
        $this->assertNotNull($employee->fresh()->synced_at);

        $employee->fresh()->update(['name_ar' => 'موظف معدل']);

        $this->assertNull($employee->fresh()->synced_at);
    }
}
