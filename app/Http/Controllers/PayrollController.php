<?php

namespace App\Http\Controllers;

use App\Models\PayrollCycle;
use App\Models\PayrollItem;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $exportableCycle = PayrollCycle::query()
            ->where('company_id', $companyId)
            ->where('status', PayrollCycle::STATUS_LOCKED)
            ->latest('year')
            ->latest('month')
            ->latest('run_sequence')
            ->first();

        return view('payroll.index', [
            'cycles' => $cycles,
            'currentCycle' => $currentCycle,
            'exportableCycle' => $exportableCycle,
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

    /**
     * Print-ready A4 payslip for a single payroll item (browser print → PDF).
     *
     * Access: payroll staff scoped to their current company, or the employee
     * themself — but self-service only sees payslips of LOCKED runs, since
     * draft/under-review figures are not official.
     */
    public function payslip(Request $request, PayrollCycle $payroll, PayrollItem $item)
    {
        abort_unless($item->payroll_cycle_id === $payroll->id, 404);

        $user = $request->user();
        $isPayrollStaff = $user->can('view-payroll')
            && $payroll->company_id === $user->current_company_id;
        $isSelf = $user->employee && $user->employee->id === $item->employee_id;

        if (! $isPayrollStaff) {
            abort_unless($isSelf && $payroll->isLocked(), 403);
        }

        $payroll->load(['company', 'branch', 'parentCycle']);
        $item->load(['employee.department', 'employee.position', 'employee.branch']);

        return view('payroll.payslip', ['cycle' => $payroll, 'item' => $item]);
    }

    /**
     * Mudad/WPS-style salary file for a LOCKED run.
     *
     * PLACEHOLDER FORMAT — column set follows the common WPS salary-file
     * shape (employee id, iqama, bank, IBAN, salary breakdown, net) but MUST
     * be verified against the official Mudad file specification before being
     * uploaded to Mudad in production (AGENT.md guiding principle #6).
     */
    public function exportMudad(Request $request, PayrollCycle $payroll): StreamedResponse
    {
        abort_unless($request->user()->can('manage-payroll'), 403);
        abort_unless($request->user()->canAccessCompany($payroll->company_id), 403);
        abort_unless($payroll->isLocked(), 422, 'تصدير ملف مداد متاح فقط للمسيرات المقفلة.');

        $payroll->load(['company', 'items.employee']);

        // Exporting IBANs + salary figures is sensitive — always audited.
        activity('payroll')
            ->performedOn($payroll)
            ->causedBy($request->user())
            ->event('mudad_export')
            ->log('Mudad salary file exported');

        $filename = sprintf(
            'mudad_wps_%s_%04d_%02d_run%d.csv',
            $payroll->company?->name_en ? str_replace(' ', '_', strtolower($payroll->company->name_en)) : $payroll->company_id,
            $payroll->year,
            $payroll->month,
            $payroll->run_sequence ?? 0,
        );

        return response()->streamDownload(function () use ($payroll) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM so Arabic names open correctly in Excel.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'employee_code', 'national_id', 'name_ar', 'name_en',
                'bank_name', 'iban',
                'basic_salary', 'housing_allowance', 'other_earnings',
                'total_deductions', 'net_salary',
            ]);

            foreach ($payroll->items as $item) {
                $employee = $item->employee;

                fputcsv($out, [
                    $employee?->employee_code,
                    $employee?->national_id,
                    $employee?->name_ar,
                    $employee?->name_en,
                    $employee?->bank_name,
                    $employee?->iban,
                    number_format((float) $item->basic_salary, 2, '.', ''),
                    number_format((float) $item->housing_allowance, 2, '.', ''),
                    number_format((float) ($item->transportation_allowance + $item->other_allowances + $item->overtime + $item->previous_dues), 2, '.', ''),
                    number_format((float) $item->total_deductions, 2, '.', ''),
                    number_format((float) $item->net_salary, 2, '.', ''),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
