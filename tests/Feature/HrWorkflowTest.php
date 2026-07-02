<?php

namespace Tests\Feature;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HrWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_core_hr_module_pages(): void
    {
        $this->seed();
        $this->actingAs(User::where('email', 'admin@hr.local')->firstOrFail());

        $this->get('/employees')
            ->assertOk()
            ->assertSee('AMNIAT')
            ->assertSee('AMNIAT Factory')
            ->assertSee('PTC')
            ->assertSee('PTC Construction')
            ->assertSee('أمنيات للأمن و السلامة')
            ->assertSee('مصنع امنيات')
            ->assertSee('تقنيات الدهان للتجارة')
            ->assertSee('تقنيات الدهان للمقاولات');
        $this->get('/leaves')->assertOk()->assertSee('الإجازات');
        $this->get('/attendance')->assertOk()->assertSee('الحضور');
        $this->get('/payroll')->assertOk()->assertSee('الرواتب');
        $this->get('/payroll/1')->assertOk()->assertSee('تفاصيل الرواتب');
    }

    public function test_leave_approval_updates_balance(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();
        $leave = LeaveRequest::where('status', 'pending')->firstOrFail();
        $balance = LeaveBalance::where('employee_id', $leave->employee_id)
            ->where('leave_type_id', $leave->leave_type_id)
            ->where('year', (int) $leave->starts_on->format('Y'))
            ->firstOrFail();

        $this->actingAs($admin)
            ->post(route('leaves.approve', $leave))
            ->assertRedirect();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        $this->assertSame((float) $balance->used_days + (float) $leave->days, (float) $balance->fresh()->used_days);
    }

    public function test_employee_role_cannot_access_sensitive_company_hr_data(): void
    {
        $this->seed();

        $employee = \App\Models\Employee::where('company_id', 1)->whereNull('user_id')->firstOrFail();
        $coworker = \App\Models\Employee::where('company_id', 1)->whereKeyNot($employee->id)->firstOrFail();
        $user = User::create([
            'name' => 'Employee User',
            'email' => 'employee.user@hr.local',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'current_company_id' => 1,
        ]);
        $user->companies()->sync([1]);
        $employee->forceFill(['user_id' => $user->id])->save();

        $this->actingAs($user);

        $this->get(route('employees.show', $employee))->assertOk();
        $this->get(route('employees.show', $coworker))->assertForbidden();
        $this->get(route('payroll.index'))->assertForbidden();
        $this->get(route('payroll.show', 1))->assertForbidden();
        $this->get(route('documents.index'))->assertForbidden();
        $this->get(route('reports.index'))->assertForbidden();
        $this->get(route('nitaqat.calculator'))->assertForbidden();
        $this->get(route('dashboard'))->assertRedirect(route('employees.show', $employee));
    }

    public function test_employee_role_cannot_approve_leave_requests(): void
    {
        $this->seed();

        $employee = \App\Models\Employee::where('company_id', 1)->whereNull('user_id')->firstOrFail();
        $user = User::create([
            'name' => 'Employee User',
            'email' => 'employee.approver@hr.local',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'current_company_id' => 1,
        ]);
        $user->companies()->sync([1]);
        $employee->forceFill(['user_id' => $user->id])->save();

        $leave = LeaveRequest::where('status', 'pending')->firstOrFail();

        $this->actingAs($user)
            ->post(route('leaves.approve', $leave))
            ->assertForbidden();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'status' => 'pending',
            'approved_by' => null,
        ]);
    }

    public function test_employee_role_can_only_create_leave_for_self(): void
    {
        $this->seed();

        $employee = \App\Models\Employee::where('company_id', 1)->whereNull('user_id')->firstOrFail();
        $coworker = \App\Models\Employee::where('company_id', 1)->whereKeyNot($employee->id)->firstOrFail();
        $leaveType = \App\Models\LeaveType::firstOrFail();
        $user = User::create([
            'name' => 'Employee User',
            'email' => 'employee.leave@hr.local',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'current_company_id' => 1,
        ]);
        $user->companies()->sync([1]);
        $employee->forceFill(['user_id' => $user->id])->save();

        $this->actingAs($user)
            ->post(route('leaves.store'), [
                'employee_id' => $coworker->id,
                'leave_type_id' => $leaveType->id,
                'starts_on' => now()->addDays(10)->toDateString(),
                'ends_on' => now()->addDays(11)->toDateString(),
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('leaves.store'), [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'starts_on' => now()->addDays(12)->toDateString(),
                'ends_on' => now()->addDays(13)->toDateString(),
            ])
            ->assertRedirect(route('leaves.index'));
    }

    public function test_non_hr_user_only_sees_own_attendance_records(): void
    {
        $this->seed();

        $employee = \App\Models\Employee::where('company_id', 1)->whereNull('user_id')->firstOrFail();
        $user = User::create([
            'name' => 'Branch User',
            'email' => 'branch.user@hr.local',
            'password' => Hash::make('password'),
            'role' => 'branch_manager',
            'current_company_id' => 1,
        ]);
        $user->companies()->sync([1]);
        $employee->forceFill(['user_id' => $user->id])->save();

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertOk();
        $response->assertSee($employee->name_ar);
        \App\Models\Employee::where('company_id', 1)
            ->whereKeyNot($employee->id)
            ->limit(2)
            ->get()
            ->each(fn ($coworker) => $response->assertDontSee($coworker->name_ar));
    }
}
