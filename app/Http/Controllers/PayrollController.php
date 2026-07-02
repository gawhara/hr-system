<?php

namespace App\Http\Controllers;

use App\Models\PayrollCycle;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('view-payroll'), 403);

        $companyId = $request->user()->current_company_id;

        $cycles = PayrollCycle::with(['company', 'branch'])
            ->withCount('items')
            ->withSum('items', 'gross_total')
            ->withSum('items', 'total_deductions')
            ->withSum('items', 'net_salary')
            ->where('company_id', $companyId)
            ->latest('year')
            ->latest('month')
            ->paginate(12);

        $currentCycle = PayrollCycle::with(['company', 'items.employee.position'])
            ->where('company_id', $companyId)
            ->latest('year')
            ->latest('month')
            ->first();

        $items = $currentCycle
            ? $currentCycle->items()->with(['employee.position'])->limit(10)->get()
            : collect();

        return view('payroll.index', [
            'cycles' => $cycles,
            'currentCycle' => $currentCycle,
            'items' => $items,
            'summary' => [
                'gross' => $items->sum('gross_total'),
                'deductions' => $items->sum('total_deductions'),
                'net' => $items->sum('net_salary'),
                'employees' => $items->count(),
                'gosi' => $items->sum('social_insurance_saudi'),
            ],
        ]);
    }

    public function show(Request $request, PayrollCycle $payroll)
    {
        abort_unless($request->user()->can('view-payroll'), 403);
        abort_unless($payroll->company_id === $request->user()->current_company_id, 403);

        $payroll->load(['company', 'branch', 'items.employee.department', 'parentCycle', 'adjustmentRuns']);

        return view('payroll.show', ['cycle' => $payroll]);
    }

    public function transition(Request $request, PayrollCycle $payroll)
    {
        abort_unless($request->user()->can('manage-payroll'), 403);
        abort_unless($request->user()->canAccessCompany($payroll->company_id), 403);

        $data = $request->validate([
            'status' => ['required', 'in:under_review,approved,locked,draft'],
        ]);

        if (! $payroll->canTransitionTo($data['status'])) {
            return back()->withErrors(['status' => 'انتقال غير مسموح لحالة المسير الحالية.']);
        }

        if ($data['status'] === PayrollCycle::STATUS_LOCKED && $payroll->hasUnsyncedData()) {
            return back()->withErrors([
                'status' => 'لا يمكن قفل المسير قبل اكتمال مزامنة بيانات الفرع مع الخادم المركزي.',
            ]);
        }

        $payroll->transitionTo($data['status'], $request->user());

        return redirect()
            ->route('payroll.show', $payroll)
            ->with('status', 'تم تحديث حالة مسير الرواتب.');
    }

    public function createAdjustment(Request $request, PayrollCycle $payroll)
    {
        abort_unless($request->user()->can('manage-payroll'), 403);
        abort_unless($request->user()->canAccessCompany($payroll->company_id), 403);
        abort_unless($payroll->isLocked(), 422, 'تسوية الرواتب متاحة فقط للمسيرات المقفلة.');

        $adjustment = PayrollCycle::create([
            'company_id' => $payroll->company_id,
            'branch_id' => $payroll->branch_id,
            'year' => $payroll->year,
            'month' => $payroll->month,
            'period_starts_on' => $payroll->period_starts_on,
            'period_ends_on' => $payroll->period_ends_on,
            'status' => PayrollCycle::STATUS_DRAFT,
            'parent_cycle_id' => $payroll->id,
            'run_sequence' => ((int) PayrollCycle::where('parent_cycle_id', $payroll->id)->max('run_sequence')) + 1,
        ]);

        return redirect()
            ->route('payroll.show', $adjustment)
            ->with('status', 'تم إنشاء مسير تسوية جديد مرتبط بالمسير المقفل.');
    }
}
