<?php

namespace Tests\Feature;

use App\Models\PayrollCycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PayrollWorkflowTest extends TestCase
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

    public function test_full_workflow_draft_review_approve_lock(): void
    {
        $this->seed();
        $admin = $this->admin();
        $cycle = $this->cycle();

        $this->assertSame('draft', $cycle->status);

        foreach (['under_review', 'approved', 'locked'] as $status) {
            $this->actingAs($admin)
                ->post(route('payroll.transition', $cycle), ['status' => $status])
                ->assertRedirect(route('payroll.show', $cycle));

            $this->assertSame($status, $cycle->fresh()->status);
        }

        $cycle->refresh();
        $this->assertSame($admin->id, $cycle->reviewed_by);
        $this->assertSame($admin->id, $cycle->approved_by);
        $this->assertSame($admin->id, $cycle->locked_by);
        $this->assertNotNull($cycle->locked_at);
    }

    public function test_skipping_states_is_rejected(): void
    {
        $this->seed();
        $cycle = $this->cycle();

        // draft → locked directly is not a legal transition
        $this->actingAs($this->admin())
            ->post(route('payroll.transition', $cycle), ['status' => 'locked'])
            ->assertSessionHasErrors('status');

        $this->assertSame('draft', $cycle->fresh()->status);
    }

    public function test_locked_items_are_immutable(): void
    {
        $this->seed();
        $admin = $this->admin();
        $cycle = $this->cycle();

        foreach (['under_review', 'approved', 'locked'] as $status) {
            $cycle->fresh()->transitionTo($status, $admin);
        }

        $item = $cycle->items()->firstOrFail();

        $this->expectException(\DomainException::class);
        $item->update(['net_salary' => 1]);
    }

    public function test_adjustment_run_created_from_locked_cycle(): void
    {
        $this->seed();
        $admin = $this->admin();
        $cycle = $this->cycle();

        // Adjustment on a non-locked cycle is rejected.
        $this->actingAs($admin)->post(route('payroll.adjustment', $cycle))->assertStatus(422);

        foreach (['under_review', 'approved', 'locked'] as $status) {
            $cycle->fresh()->transitionTo($status, $admin);
        }

        $this->actingAs($admin)->post(route('payroll.adjustment', $cycle))->assertRedirect();

        $adjustment = PayrollCycle::where('parent_cycle_id', $cycle->id)->firstOrFail();
        $this->assertSame('draft', $adjustment->status);
        $this->assertSame(1, $adjustment->run_sequence);
        $this->assertSame($cycle->month, $adjustment->month);
    }

    public function test_branch_node_cannot_lock_unsynced_run(): void
    {
        $this->seed();
        config(['hr.sync.role' => 'branch']);

        $admin = $this->admin();
        $cycle = $this->cycle();
        $cycle->transitionTo('under_review', $admin);
        $cycle->fresh()->transitionTo('approved', $admin);

        // Items were just seeded and never synced → lock must be refused.
        $this->actingAs($admin)
            ->post(route('payroll.transition', $cycle->fresh()), ['status' => 'locked'])
            ->assertSessionHasErrors('status');

        $this->assertSame('approved', $cycle->fresh()->status);
    }

    public function test_employee_role_cannot_transition_payroll(): void
    {
        $this->seed();
        $cycle = $this->cycle();

        $user = User::create([
            'name' => 'Employee User',
            'email' => 'payroll.employee@hr.local',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'current_company_id' => 1,
        ]);
        $user->companies()->sync([1]);

        $this->actingAs($user)
            ->post(route('payroll.transition', $cycle), ['status' => 'under_review'])
            ->assertForbidden();
    }
}
