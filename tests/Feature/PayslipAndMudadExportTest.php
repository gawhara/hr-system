<?php

namespace Tests\Feature;

use App\Models\PayrollCycle;
use App\Models\PayrollItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PayslipAndMudadExportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@hr.local')->firstOrFail();
    }

    private function cycle(): PayrollCycle
    {
        return PayrollCycle::where('company_id', 1)->firstOrFail();
    }

    private function lock(PayrollCycle $cycle): PayrollCycle
    {
        $admin = $this->admin();

        foreach (['under_review', 'approved', 'locked'] as $status) {
            $cycle->fresh()->transitionTo($status, $admin);
        }

        return $cycle->fresh();
    }

    private function selfServiceUserFor(PayrollItem $item): User
    {
        $user = User::create([
            'name' => 'Self Service User',
            'email' => 'payslip.self@hr.local',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'current_company_id' => 1,
        ]);
        $user->companies()->sync([1]);

        $item->employee->update(['user_id' => $user->id]);

        return $user;
    }

    public function test_payroll_staff_can_view_payslip_at_any_status(): void
    {
        $this->seed();
        $cycle = $this->cycle();
        $item = $cycle->items()->with('employee')->firstOrFail();

        $this->actingAs($this->admin())
            ->get(route('payroll.payslip', [$cycle, $item]))
            ->assertOk()
            ->assertSee('قسيمة راتب')
            ->assertSee($item->employee->name_ar);
    }

    public function test_payslip_item_must_belong_to_cycle(): void
    {
        $this->seed();
        $cycle = $this->cycle();
        $foreignItem = PayrollItem::where('payroll_cycle_id', '!=', $cycle->id)->firstOrFail();

        $this->actingAs($this->admin())
            ->get(route('payroll.payslip', [$cycle, $foreignItem]))
            ->assertNotFound();
    }

    public function test_employee_sees_own_payslip_only_once_locked(): void
    {
        $this->seed();
        $cycle = $this->cycle();
        $item = $cycle->items()->with('employee')->firstOrFail();
        $user = $this->selfServiceUserFor($item);

        // Draft run → not official yet, self-service is refused.
        $this->actingAs($user)
            ->get(route('payroll.payslip', [$cycle, $item]))
            ->assertForbidden();

        $cycle = $this->lock($cycle);

        $this->actingAs($user)
            ->get(route('payroll.payslip', [$cycle, $item]))
            ->assertOk()
            ->assertSee($item->employee->name_ar);
    }

    public function test_employee_cannot_view_someone_elses_payslip(): void
    {
        $this->seed();
        $cycle = $this->cycle();
        $items = $cycle->items()->with('employee')->take(2)->get();
        $this->assertTrue($items->count() >= 2, 'Seeder must provide at least two payroll items.');

        $user = $this->selfServiceUserFor($items[0]);
        $cycle = $this->lock($cycle);

        $this->actingAs($user)
            ->get(route('payroll.payslip', [$cycle, $items[1]]))
            ->assertForbidden();
    }

    public function test_mudad_export_requires_locked_cycle(): void
    {
        $this->seed();
        $cycle = $this->cycle();

        $this->actingAs($this->admin())
            ->get(route('payroll.export.mudad', $cycle))
            ->assertStatus(422);
    }

    public function test_mudad_export_streams_csv_for_locked_cycle(): void
    {
        $this->seed();
        $cycle = $this->lock($this->cycle());
        $employee = $cycle->items()->with('employee')->firstOrFail()->employee;

        $response = $this->actingAs($this->admin())
            ->get(route('payroll.export.mudad', $cycle))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('employee_code', $csv);
        $this->assertStringContainsString('iban', $csv);
        $this->assertStringContainsString($employee->name_ar, $csv);

        // Sensitive export is audited.
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'payroll',
            'event' => 'mudad_export',
            'subject_id' => $cycle->id,
        ]);
    }

    public function test_employee_role_cannot_export_mudad_file(): void
    {
        $this->seed();
        $cycle = $this->cycle();
        $item = $cycle->items()->firstOrFail();
        $user = $this->selfServiceUserFor($item);
        $cycle = $this->lock($cycle);

        $this->actingAs($user)
            ->get(route('payroll.export.mudad', $cycle))
            ->assertForbidden();
    }
}
