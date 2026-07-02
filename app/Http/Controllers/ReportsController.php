<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollItem;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless($request->user()->can('view-reports'), 403);

        $user = $request->user();
        $companyIds = $user->isGroupAdmin()
            ? Company::pluck('id')
            : $user->companies()->pluck('companies.id');

        $employees = Employee::with(['company', 'department'])
            ->whereIn('company_id', $companyIds);

        $headcount = (clone $employees)->count();
        $saudis = (clone $employees)->where('saudi_non_saudi', 'saudi')->count();
        $payroll = PayrollItem::whereHas('employee', fn ($query) => $query->whereIn('company_id', $companyIds));
        $pendingLeaves = LeaveRequest::whereHas('employee', fn ($query) => $query->whereIn('company_id', $companyIds))
            ->where('status', 'pending')
            ->count();

        $companyBreakdown = Company::whereIn('id', $companyIds)
            ->withCount('employees')
            ->orderBy('name_en')
            ->get()
            ->map(function (Company $company) use ($headcount) {
                return [
                    'name' => $company->name_ar,
                    'name_en' => $company->name_en,
                    'employees' => $company->employees_count,
                    'percentage' => $headcount > 0 ? round(($company->employees_count / $headcount) * 100) : 0,
                ];
            });

        $departmentBreakdown = (clone $employees)
            ->selectRaw('departments.name_ar as name, count(*) as total')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->groupBy('departments.name_ar')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $recentEmployees = (clone $employees)
            ->latest('contract_start_date')
            ->limit(5)
            ->get();

        return view('reports.index', [
            'metrics' => [
                'headcount' => $headcount,
                'saudization' => $headcount > 0 ? round(($saudis / $headcount) * 100, 1) : 0,
                'payroll' => (clone $payroll)->sum('net_salary'),
                'pendingLeaves' => $pendingLeaves,
                'companies' => $companyIds->count(),
            ],
            'companyBreakdown' => $companyBreakdown,
            'departmentBreakdown' => $departmentBreakdown,
            'recentEmployees' => $recentEmployees,
        ]);
    }
}
