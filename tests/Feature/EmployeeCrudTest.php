<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeCrudTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return $overrides + [
            'company_id' => 1,
            'name_ar' => 'موظف جديد للاختبار',
            'saudi_non_saudi' => 'saudi',
            'national_id' => '1099999999',
            'basic_salary' => 5500,
            'status' => 'active',
        ];
    }

    public function test_hr_admin_can_create_employee(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();

        $this->actingAs($admin)->get(route('employees.create'))->assertOk();

        $response = $this->actingAs($admin)->post(route('employees.store'), $this->validPayload());

        $employee = Employee::whereNationalId('1099999999')->first();

        $this->assertNotNull($employee);
        $response->assertRedirect(route('employees.show', $employee));
        $this->assertSame('موظف جديد للاختبار', $employee->name_ar);
        $this->assertSame($admin->id, $employee->created_by);
        $this->assertNotNull($employee->uuid);
    }

    public function test_duplicate_national_id_is_rejected_via_hash(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();
        $existing = Employee::firstOrFail();

        $response = $this->actingAs($admin)
            ->from(route('employees.create'))
            ->post(route('employees.store'), $this->validPayload([
                'national_id' => $existing->national_id,
            ]));

        $response->assertRedirect(route('employees.create'));
        $response->assertSessionHasErrors('national_id');
    }

    public function test_hr_admin_can_update_employee_and_change_is_audited(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();
        $employee = Employee::firstOrFail();

        $response = $this->actingAs($admin)->put(route('employees.update', $employee), $this->validPayload([
            'company_id' => $employee->company_id,
            'name_ar' => $employee->name_ar,
            'national_id' => $employee->national_id,
            'basic_salary' => 9999,
        ]));

        $response->assertRedirect(route('employees.show', $employee));
        $this->assertEquals(9999, (float) $employee->fresh()->basic_salary);

        $activity = \Spatie\Activitylog\Models\Activity::where('log_name', 'employee')
            ->where('subject_id', $employee->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($admin->id, $activity->causer_id);
    }

    public function test_hr_admin_can_save_spreadsheet_employee_fields(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('employees.store'), $this->validPayload([
            'national_id' => '1088888888',
            'financial_employee_id' => 'FIN-XLS-1',
            'hr_employee_id' => 'HR-XLS-1',
            'full_name_arabic' => 'اسم كامل من الملف',
            'full_name_english' => 'Full Name From Sheet',
            'iqama_full_name_arabic' => 'اسم الإقامة',
            'iqama_full_name_english' => 'Iqama Name',
            'passport_full_name_arabic' => 'اسم الجواز',
            'passport_full_name_english' => 'Passport Name',
            'phone_2' => '0599999999',
            'job_title' => 'Spreadsheet Job Title',
            'branch_text' => 'Spreadsheet Branch',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'bank' => 'Bank From Sheet',
            'overtime' => 125.50,
            'training_labor_wages' => 300,
            'previous_dues' => 400,
            'total' => 6325.50,
            'basic_salary_gosi' => 5000,
            'housing_allowance_gosi' => 1250,
            'other_gosi_items' => 100,
            'diff_registered_housing_allowance' => 25,
            'absence_deduction' => 10,
            'delay_deduction' => 20,
            'leave_deduction' => 30,
            'warnings_penalties' => 40,
            'insurance_deduction' => 50,
            'loans' => 60,
            'social_insurance_saudi' => 70,
            'total_deductions' => 280,
            'cash' => 100,
            'al_rajhi_transfer' => 200,
            'bank_albilad_transfer' => 300,
            'riyad_bank_transfer' => 400,
            'remaining_salary' => 500,
            'employment_status' => 'on_duty',
        ]));

        $employee = Employee::whereNationalId('1088888888')->firstOrFail();

        $response->assertRedirect(route('employees.show', $employee));
        $this->assertSame('FIN-XLS-1', $employee->financial_employee_id);
        $this->assertSame('Spreadsheet Branch', $employee->branch_text);
        $this->assertEquals(125.50, (float) $employee->overtime);
        $this->assertEquals(280, (float) $employee->total_deductions);
        $this->assertSame('on_duty', $employee->employment_status);
    }

    public function test_employee_role_cannot_create_or_edit(): void
    {
        $this->seed();
        $user = User::create([
            'name' => 'Employee User',
            'email' => 'plain.employee@hr.local',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'current_company_id' => 1,
        ]);
        $user->companies()->sync([1]);

        $employee = Employee::firstOrFail();

        $this->actingAs($user)->get(route('employees.create'))->assertForbidden();
        $this->actingAs($user)->post(route('employees.store'), $this->validPayload())->assertForbidden();
        $this->actingAs($user)->get(route('employees.edit', $employee))->assertForbidden();
        $this->actingAs($user)->put(route('employees.update', $employee), $this->validPayload())->assertForbidden();
    }

    public function test_hr_manager_cannot_create_employee_in_other_company(): void
    {
        $this->seed();
        $hr = User::where('email', 'hr1@hr.local')->firstOrFail(); // company 1 only

        $this->actingAs($hr)
            ->post(route('employees.store'), $this->validPayload(['company_id' => 2]))
            ->assertForbidden();
    }
}
