<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\EconomicActivity;
use App\Models\Employee;
use App\Models\NitaqatCalculationBatch;
use App\Services\NitaqatCalculatorService;
use Illuminate\Http\Request;

class NitaqatController extends Controller
{
    public function __construct(private readonly NitaqatCalculatorService $calculator)
    {
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->can('view-nitaqat'), 403);

        return view('nitaqat.calculator', [
            'activities' => EconomicActivity::where('is_active', true)->orderBy('name_ar')->get(),
            'companies' => $this->availableCompanies($request),
            'selectedCompanyId' => $request->user()->current_company_id,
            'result' => null,
            'activity' => null,
            'company' => null,
        ]);
    }

    public function calculate(Request $request)
    {
        abort_unless($request->user()->can('view-nitaqat'), 403);

        $data = $request->validate([
            'economic_activity_id' => ['required', 'exists:economic_activities,id'],
            'company_id' => ['required', 'exists:companies,id'],
        ]);

        abort_unless($request->user()->canAccessCompany((int) $data['company_id']), 403);

        $activity = EconomicActivity::findOrFail($data['economic_activity_id']);
        $company = Company::findOrFail($data['company_id']);
        // Employed = active/probation/on_leave/suspended - Nitaqat counts
        // registered employees, not only status='active' rows.
        $employees = Employee::where('company_id', $company->id)
            ->employed()
            ->orderBy('employee_code')
            ->get()
            ->map(fn (Employee $employee) => $this->calculator->employeePayload($employee))
            ->values()
            ->all();

        $result = $this->calculator->calculateEstablishment($employees, $activity);

        NitaqatCalculationBatch::create([
            'economic_activity_id' => $activity->id,
            'company_id' => $company->id,
            'total_employees' => $result['total_employees'],
            'total_saudis_headcount' => $result['total_saudis_headcount'],
            'total_weighted_saudis' => $result['total_weighted_saudis'],
            'achieved_percentage' => $result['achieved_percentage'],
            'required_percentage' => $result['required_percentage'],
            'band' => $result['band'],
            'additional_saudis_needed' => $result['additional_saudis_needed'],
            'raw_input' => $employees,
            'breakdown' => $result['breakdown'],
            'created_by' => $request->user()->id,
        ]);

        return view('nitaqat.calculator', [
            'activities' => EconomicActivity::where('is_active', true)->orderBy('name_ar')->get(),
            'companies' => $this->availableCompanies($request),
            'selectedCompanyId' => (int) $data['company_id'],
            'result' => $result,
            'activity' => $activity,
            'company' => $company,
        ]);
    }

    private function availableCompanies(Request $request)
    {
        $user = $request->user();

        return $user->isGroupAdmin()
            ? Company::orderBy('name_ar')->get()
            : $user->companies()->orderBy('name_ar')->get();
    }
}
