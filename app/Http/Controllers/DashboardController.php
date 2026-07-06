<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollItem;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        if (! $request->user()->isHrAdmin()) {
            $employee = $request->user()->employee;

            abort_unless($employee, 403);

            return redirect()->route('employees.show', $employee);
        }

        $companies = $this->dashboardCompanies($request->user());

        return view('dashboard', [
            'companyDashboards' => $companies->map(fn (Company $company) => $this->companyDashboard($company)),
        ]);
    }

    public function showCompany(Request $request, Company $company)
    {
        abort_unless($request->user()->canViewSensitiveHr(), 403);
        abort_unless($request->user()->canAccessCompany($company->id), 403);

        $dashboard = $this->companyDashboard($company);
        $employees = Employee::query()->where('company_id', $company->id);
        $headcount = max((int) $dashboard['headcount'], 1);

        $departmentRows = (clone $employees)
            ->selectRaw('department_id, COUNT(*) as total')
            ->whereNotNull('department_id')
            ->groupBy('department_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $departments = Department::whereIn('id', $departmentRows->pluck('department_id'))
            ->get()
            ->keyBy('id');

        $departmentChart = $departmentRows->map(fn ($row) => [
            'name' => $departments->get($row->department_id)?->name_ar ?? 'غير محدد',
            'total' => (int) $row->total,
            'percent' => round(((int) $row->total / $headcount) * 100),
        ]);

        return view('companies.dashboard', [
            'company' => $company,
            'dashboard' => $dashboard,
            'localizationPercent' => round(((int) $dashboard['saudis'] / $headcount) * 100),
            'activeEmployees' => (clone $employees)->where('status', 'active')->count(),
            'branchesCount' => $company->branches()->count(),
            'departmentsCount' => Department::whereHas('branch', fn ($query) => $query->where('company_id', $company->id))->count(),
            'averageSalary' => (clone $employees)->avg('basic_salary') ?? 0,
            'departmentChart' => $departmentChart,
            'genderChart' => [
                'male' => (clone $employees)->where('gender', 'male')->count(),
                'female' => (clone $employees)->where('gender', 'female')->count(),
            ],
        ]);
    }

    private function dashboardCompanies($user): Collection
    {
        return $user->isGroupAdmin()
            ? Company::orderBy('id')->get()
            : $user->companies()->orderBy('companies.id')->get();
    }

    private function companyDashboard(Company $company): array
    {
        $employees = Employee::query()->where('company_id', $company->id);
        $headcount = (clone $employees)->count();
        $saudis = (clone $employees)->where('saudi_non_saudi', 'saudi')->count();
        $nonSaudis = (clone $employees)->where('saudi_non_saudi', 'non_saudi')->count();
        $localizationPercent = $headcount > 0 ? round(($saudis / $headcount) * 100) : 0;

        $documentAlerts = (clone $employees)
            ->where(function ($query) {
                $query->whereBetween('iqama_expiry', [now(), now()->addDays(45)])
                    ->orWhereBetween('passport_expiry', [now(), now()->addDays(45)])
                    ->orWhereBetween('contract_end_date', [now(), now()->addDays(45)]);
            })
            ->count();

        return [
            'company' => $company,
            'logo' => $this->companyLogo($company),
            'theme' => $this->companyTheme($company),
            'headcount' => $headcount,
            'saudis' => $saudis,
            'non_saudis' => $nonSaudis,
            'localization_percent' => $localizationPercent,
            'nitaqat_band' => $this->nitaqatBand($localizationPercent),
            'pending_leaves' => LeaveRequest::whereHas('employee', fn ($query) => $query->where('company_id', $company->id))
                ->where('status', 'pending')
                ->count(),
            // B1: the card says "net payroll" for the month — sum only the
            // latest run, not every payroll item ever created.
            'monthly_payroll' => ($latestCycleId = \App\Models\PayrollCycle::where('company_id', $company->id)
                    ->orderByDesc('year')->orderByDesc('month')->orderByDesc('run_sequence')
                    ->value('id'))
                ? PayrollItem::where('payroll_cycle_id', $latestCycleId)->sum('net_salary')
                : 0,
            'document_alerts' => $documentAlerts,
        ];
    }

    private function companyLogo(Company $company): string
    {
        if ($company->logo_path) {
            return $company->logo_path;
        }

        $name = mb_strtolower($company->name_en.' '.$company->name_ar);

        return match (true) {
            str_contains($name, 'factory') || str_contains($name, 'مصنع') => 'images/companies/amniat-factory.png',
            str_contains($name, 'construction') || str_contains($name, 'مقاولات') || str_contains($name, 'المقاولات') => 'images/companies/ptc-construction.png',
            str_contains($name, 'ptc') || str_contains($name, 'تجارة') || str_contains($name, 'التجارة') => 'images/companies/ptc.png',
            default => 'images/companies/amniat.png',
        };
    }

    private function companyTheme(Company $company): array
    {
        $name = mb_strtolower($company->name_en.' '.$company->name_ar);

        if (str_contains($name, 'ptc') || str_contains($name, 'تقنيات') || str_contains($name, 'دهان')) {
            return [
                'from' => '#062b63',
                'to' => '#174ea6',
                'soft' => '#eef4ff',
                'text' => '#062b63',
                'muted' => '#dbeafe',
                'dark' => '#031b3f',
                'ring' => 'rgba(6, 43, 99, 0.16)',
            ];
        }

        return [
            'from' => '#0b0b0c',
            'to' => '#f59e0b',
            'soft' => '#fff7ed',
            'text' => '#111827',
            'muted' => '#fed7aa',
            'dark' => '#111111',
            'ring' => 'rgba(245, 158, 11, 0.22)',
        ];
    }

    private function nitaqatBand(int $percent): array
    {
        foreach (config('nitaqat.bands') as $band) {
            if ($percent >= $band['minimum_percent']) {
                return $band;
            }
        }

        return config('nitaqat.bands')[array_key_last(config('nitaqat.bands'))];
    }
}
