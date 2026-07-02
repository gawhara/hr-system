<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\PayrollCycle;
use App\Models\PayrollItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeProfileTabsTest extends TestCase
{
    use RefreshDatabase;

    private function makeSelfServiceUser(Employee $employee): User
    {
        $user = User::create([
            'name' => 'Employee User',
            'email' => 'self.service@hr.local',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'current_company_id' => $employee->company_id,
        ]);
        $user->companies()->sync([$employee->company_id]);
        $employee->forceFill(['user_id' => $user->id])->save();

        return $user;
    }

    public function test_employee_can_see_own_payslips_and_attendance_on_profile(): void
    {
        $this->seed();
        $employee = Employee::where('company_id', 1)->whereNull('user_id')->firstOrFail();
        $user = $this->makeSelfServiceUser($employee);

        $cycle = PayrollCycle::where('company_id', 1)->firstOrFail();
        PayrollItem::where('payroll_cycle_id', $cycle->id)->where('employee_id', $employee->id)->delete();
        PayrollItem::create([
            'payroll_cycle_id' => $cycle->id,
            'employee_id' => $employee->id,
            'gross_total' => 5000,
            'total_deductions' => 200,
            'net_salary' => 4800,
        ]);

        AttendanceRecord::where('employee_id', $employee->id)->delete();
        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $response = $this->actingAs($user)->get(route('employees.show', $employee));

        $response->assertOk();
        $response->assertSee('كشف الرواتب');
        $response->assertSee('4,800.00');
        $response->assertSee('الحضور والانصراف');
        // Self-service must not get a link into the shared payroll cycle view.
        $response->assertDontSee(route('payroll.show', $cycle));
    }

    public function test_employee_cannot_see_activity_tab_but_hr_can(): void
    {
        $this->seed();
        $employee = Employee::where('company_id', 1)->whereNull('user_id')->firstOrFail();
        $user = $this->makeSelfServiceUser($employee);
        $employee->update(['basic_salary' => $employee->basic_salary + 1]);

        $selfResponse = $this->actingAs($user)->get(route('employees.show', $employee));
        $selfResponse->assertOk();
        $selfResponse->assertDontSee('سجل النشاط');

        $admin = User::where('email', 'admin@hr.local')->firstOrFail();
        $hrResponse = $this->actingAs($admin)->get(route('employees.show', $employee));
        $hrResponse->assertOk();
        $hrResponse->assertSee('سجل النشاط');
        $hrResponse->assertSee('basic_salary');
    }

    public function test_hr_sees_payroll_link_into_full_cycle(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();
        $employee = Employee::where('company_id', 1)->firstOrFail();
        $cycle = PayrollItem::where('employee_id', $employee->id)->firstOrFail()->payroll_cycle_id;

        $response = $this->actingAs($admin)->get(route('employees.show', $employee));

        $response->assertOk();
        $response->assertSee(route('payroll.show', $cycle));
    }

    public function test_profile_completion_percent_reflects_filled_fields(): void
    {
        $employee = new Employee([
            'email' => 'a@b.com',
            'phone' => '0500000000',
            'national_id' => '1000000000',
            'bank_name' => 'Al Rajhi Bank',
            'iban' => 'SA0000000000000000000000',
            'department_id' => 1,
            'position_id' => 1,
            'contract_type' => 'fixed',
            'birth_date' => now()->subYears(30),
            'nationality' => null,
        ]);

        $completion = $employee->profileCompletion();

        $this->assertSame(41, $employee->profile_completion_percent);
        $this->assertSame(41, $completion['percent']);
        $this->assertContains('الاسم بالعربية', $completion['missing']);
        $this->assertContains('الجنسية', $completion['missing']);
        $this->assertContains('عقد عمل مسجل', $completion['missing']);
    }

    public function test_index_page_shows_completion_ring_for_each_employee(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('employees.index'));

        $response->assertOk();
        $response->assertSee('اكتمال الملف');
    }
}
