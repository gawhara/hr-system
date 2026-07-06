<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollCycle;
use App\Models\User;
use App\Services\Sync\SyncApplier;
use App\Services\Sync\SyncRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the ANALYSIS.md fixes (S1, S3, S7, S8, B1, B2,
 * FR-005).
 */
class AnalysisFixesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@hr.local')->firstOrFail();
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        $this->seed();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'admin@hr.local', 'password' => 'wrong'])
                ->assertRedirect();
        }

        $this->post('/login', ['email' => 'admin@hr.local', 'password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_sync_applier_ignores_non_writable_columns(): void
    {
        $this->seed();

        $employee = Employee::firstOrFail();
        $employee->markSynced();

        $originalId = $employee->id;
        $originalCreator = $employee->created_by;

        $record = SyncRegistry::serialize($employee->fresh());
        $record['updated_at'] = now()->addMinute()->toIso8601String();
        // Hostile payload: try to reassign identity/ownership columns and
        // smuggle an unknown key.
        $record['attributes']['id'] = 999999;
        $record['attributes']['created_by'] = 999999;
        $record['attributes']['user_id'] = 999999;
        $record['attributes']['totally_fake_column'] = 'x';
        $record['attributes']['phone_2'] = '0511111111';

        $status = app(SyncApplier::class)->apply($record);

        $this->assertSame(SyncApplier::APPLIED, $status);

        $fresh = $employee->fresh();
        $this->assertSame($originalId, $fresh->id);
        $this->assertSame($originalCreator, $fresh->created_by);
        // Legitimate writable column still applied.
        $this->assertSame('0511111111', $fresh->phone_2);
    }

    public function test_attendance_rejects_malformed_date(): void
    {
        $this->seed();

        $this->actingAs($this->admin())
            ->from(route('dashboard'))
            ->get(route('attendance.index', ['date' => 'not-a-date']))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('date');

        $this->actingAs($this->admin())
            ->get(route('attendance.index', ['date' => now()->toDateString()]))
            ->assertOk();
    }

    public function test_overlapping_leave_request_is_rejected(): void
    {
        $this->seed();
        $admin = $this->admin();

        $existing = LeaveRequest::where('status', 'pending')->firstOrFail();
        $employee = $existing->employee;
        $admin->update(['current_company_id' => $employee->company_id]);

        $this->actingAs($admin)
            ->from(route('leaves.create'))
            ->post(route('leaves.store'), [
                'employee_id' => $employee->id,
                'leave_type_id' => $existing->leave_type_id,
                'starts_on' => $existing->starts_on->toDateString(),
                'ends_on' => $existing->ends_on->addDay()->toDateString(),
            ])
            ->assertRedirect(route('leaves.create'))
            ->assertSessionHasErrors('starts_on');
    }

    public function test_paid_leave_approval_blocked_when_balance_insufficient(): void
    {
        $this->seed();
        $admin = $this->admin();

        $employee = Employee::where('company_id', 1)->whereDoesntHave('leaveRequests')->firstOrFail();
        $annual = LeaveType::where('name_en', 'Annual Leave')->firstOrFail();

        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annual->id,
            'starts_on' => now()->addMonth()->toDateString(),
            'ends_on' => now()->addMonth()->addDays(29)->toDateString(),
            'days' => 30, // entitled is 21 — must be refused
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('leaves.approve', $leave))
            ->assertSessionHasErrors('leave');

        $this->assertSame('pending', $leave->fresh()->status);

        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $annual->id)
            ->first();

        $this->assertEquals(0, (float) ($balance?->used_days ?? 0));
    }

    public function test_group_admin_can_soft_delete_employee_but_hr_cannot(): void
    {
        $this->seed();

        $employee = Employee::where('company_id', 1)->whereNull('user_id')->firstOrFail();

        $hr = User::where('email', 'hr1@hr.local')->firstOrFail();
        $this->actingAs($hr)->delete(route('employees.destroy', $employee))->assertForbidden();

        $this->actingAs($this->admin())
            ->delete(route('employees.destroy', $employee))
            ->assertRedirect();

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_reports_payroll_metric_uses_latest_cycle_only(): void
    {
        $this->seed();

        // A newer cycle for company 1 supersedes the seeded one.
        $newCycle = PayrollCycle::create([
            'company_id' => 1,
            'year' => (int) now()->addYear()->format('Y'),
            'month' => 1,
            'period_starts_on' => now()->addYear()->startOfYear()->toDateString(),
            'period_ends_on' => now()->addYear()->startOfYear()->endOfMonth()->toDateString(),
        ]);

        $employee = Employee::where('company_id', 1)->firstOrFail();
        $newCycle->items()->create(['employee_id' => $employee->id, 'net_salary' => 1234]);

        // hr1 is scoped to company 1 only → metric must equal the new cycle.
        $hr = User::where('email', 'hr1@hr.local')->firstOrFail();

        $response = $this->actingAs($hr)->get(route('reports.index'))->assertOk();

        $this->assertEquals(1234.0, (float) $response->viewData('metrics')['payroll']);
    }
}
