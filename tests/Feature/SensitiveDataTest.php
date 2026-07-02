<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class SensitiveDataTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(array $attributes = []): Employee
    {
        $company = Company::create([
            'name_ar' => 'شركة اختبار',
            'name_en' => 'Test Co',
        ]);

        return Employee::create($attributes + [
            'company_id' => $company->id,
            'name_ar' => 'موظف حساس',
            'national_id' => '1234567890',
            'iban' => 'SA0380000000608010167519',
            'passport_id' => 'P900001',
            'basic_salary' => 6000,
        ]);
    }

    public function test_pii_is_encrypted_at_rest_and_readable_via_model(): void
    {
        $employee = $this->makeEmployee();

        $raw = DB::table('employees')->where('id', $employee->id)->first();

        $this->assertNotSame('1234567890', $raw->national_id);
        $this->assertNotSame('SA0380000000608010167519', $raw->iban);
        $this->assertNotSame('P900001', $raw->passport_id);

        $fresh = $employee->fresh();
        $this->assertSame('1234567890', $fresh->national_id);
        $this->assertSame('SA0380000000608010167519', $fresh->iban);
        $this->assertSame('P900001', $fresh->passport_id);
    }

    public function test_national_id_exact_search_works_via_hash(): void
    {
        $employee = $this->makeEmployee();

        $found = Employee::whereNationalId('1234567890')->first();

        $this->assertTrue($found?->is($employee));
        $this->assertNull(Employee::whereNationalId('9999999999')->first());
    }

    public function test_salary_change_is_audited_with_old_and_new_values(): void
    {
        $employee = $this->makeEmployee();

        $employee->update(['basic_salary' => 7500]);

        $activity = Activity::where('log_name', 'employee')
            ->where('subject_id', $employee->id)
            ->where('event', 'updated')
            ->latest('id')
            ->firstOrFail();

        $this->assertEquals(7500, $activity->properties['attributes']['basic_salary']);
        $this->assertEquals(6000, $activity->properties['old']['basic_salary']);
    }

    public function test_audit_log_masks_encrypted_pii_values(): void
    {
        $employee = $this->makeEmployee();

        $employee->update(['iban' => 'SA4420000001234567891234']);

        $activity = Activity::where('log_name', 'employee')
            ->where('subject_id', $employee->id)
            ->where('event', 'updated')
            ->latest('id')
            ->firstOrFail();

        $loggedNew = $activity->properties['attributes']['iban'];
        $loggedOld = $activity->properties['old']['iban'];

        $this->assertStringNotContainsString('20000001234567891234', $loggedNew);
        $this->assertStringNotContainsString('80000000608010167519', $loggedOld);
        $this->assertStringStartsWith('SA', $loggedNew);
        $this->assertStringContainsString('*', $loggedNew);
    }
}
