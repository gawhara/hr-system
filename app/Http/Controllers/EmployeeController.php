<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Shift;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $selectedCompanyId = $request->integer('company_id') ?: null;
        $companyIds = $user->isGroupAdmin()
            ? Company::pluck('id')
            : $user->companies()->pluck('companies.id');
        $companyId = $user->isGroupAdmin()
            ? $selectedCompanyId
            : $user->current_company_id;
        $search = trim((string) $request->query('search'));

        abort_if($selectedCompanyId && ! $companyIds->contains($selectedCompanyId), 403);

        $companies = Company::whereIn('id', $companyIds)
            ->withCount('employees')
            ->orderBy('name_en')
            ->get();

        $filters = [
            'branch_id' => $request->integer('branch_id') ?: null,
            'department_id' => $request->integer('department_id') ?: null,
            'status' => in_array($request->query('status'), Employee::STATUSES, true) ? $request->query('status') : null,
            'contract_type' => in_array($request->query('contract_type'), Employee::CONTRACT_TYPES, true) ? $request->query('contract_type') : null,
        ];

        $baseQuery = Employee::whereIn('company_id', $companyIds)
            ->when(! $user->isHrAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId));

        $employees = (clone $baseQuery)
            ->with(['company', 'branch', 'department', 'position'])
            ->when($filters['branch_id'], fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['department_id'], fn ($query, $departmentId) => $query->where('department_id', $departmentId))
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['contract_type'], fn ($query, $contractType) => $query->where('contract_type', $contractType))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    // national_id is encrypted at rest: only exact-match search
                    // is possible, via its deterministic hash column.
                    $query->where('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%")
                        ->orWhere('national_id_hash', hash('sha256', $search));
                });
            })
            ->orderBy('employee_code')
            ->paginate(12)
            ->withQueryString();

        return view('employees.index', [
            'companies' => $companies,
            'branches' => Branch::whereIn('company_id', $companyIds)->orderBy('name_ar')->get(),
            'departments' => Department::whereHas('branch', fn ($query) => $query->whereIn('company_id', $companyIds))->orderBy('name_ar')->get(),
            'employees' => $employees,
            'search' => $search,
            'selectedCompanyId' => $companyId,
            'filters' => $filters,
            'dataQuality' => [
                'probation' => (clone $baseQuery)->where('status', 'probation')->count(),
                'expired_iqama' => (clone $baseQuery)->whereDate('iqama_expiry', '<', now())->count(),
                'incomplete' => (clone $baseQuery)->where('profile_completion', '<', 75)->count(),
            ],
        ]);
    }

    public function updateStatus(Request $request, Employee $employee)
    {
        abort_unless($request->user()->can('manage-employees'), 403);
        abort_unless($request->user()->canAccessCompany($employee->company_id), 403);

        $data = $request->validate([
            'status' => ['required', \Illuminate\Validation\Rule::in(Employee::STATUSES)],
            // Deactivating states always need a recorded reason (PRD FR-004).
            'reason' => ['nullable', 'string', 'max:2000', 'required_if:status,inactive,suspended,resigned,terminated'],
        ]);

        $employee->changeStatus($data['status'], $data['reason'] ?? null, $request->user());

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', 'تم تحديث حالة الموظف.');
    }

    public function show(Request $request, Employee $employee)
    {
        $user = $request->user();
        abort_unless($user->canViewEmployee($employee), 403);

        $isSelf = $employee->user_id === $user->id;
        $canSeePayroll = $user->can('view-payroll') || $isSelf;
        $canSeeActivity = $user->can('view-sensitive-hr');

        $employee->load([
            'company', 'branch', 'department', 'position', 'shift', 'manager',
            'statusHistories.changer',
            'leaveBalances.leaveType',
            'leaveRequests' => fn ($query) => $query->with('leaveType')->latest('starts_on')->limit(10),
            'documents.type',
            'contracts' => fn ($query) => $query->latest('starts_on'),
            'attendanceRecords' => fn ($query) => $query->latest('work_date')->limit(15),
        ]);

        if ($canSeePayroll) {
            $employee->load(['payrollItems' => fn ($query) => $query->with('cycle')->latest('id')->limit(12)]);
        }

        return view('employees.show', [
            'employee' => $employee,
            'isSelf' => $isSelf,
            'canSeePayroll' => $canSeePayroll,
            'canSeeActivity' => $canSeeActivity,
            'timeline' => $canSeeActivity ? $employee->activityTimeline() : collect(),
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('manage-employees'), 403);

        return view('employees.create', $this->formOptions($request) + [
            'employee' => new Employee([
                'status' => 'active',
                'saudi_non_saudi' => 'saudi',
            ]),
        ]);
    }

    public function store(EmployeeRequest $request)
    {
        $employee = Employee::create($request->validated() + [
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', 'تم إنشاء ملف الموظف بنجاح.');
    }

    public function edit(Request $request, Employee $employee)
    {
        abort_unless($request->user()->can('manage-employees'), 403);
        abort_unless($request->user()->canAccessCompany($employee->company_id), 403);

        return view('employees.edit', $this->formOptions($request) + [
            'employee' => $employee,
        ]);
    }

    public function update(EmployeeRequest $request, Employee $employee)
    {
        abort_unless($request->user()->canAccessCompany($employee->company_id), 403);

        $employee->update($request->validated() + [
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', 'تم تحديث بيانات الموظف بنجاح.');
    }

    public function destroy(Request $request, Employee $employee)
    {
        // FR-005: deleting an employee file is group-admin-only and always a
        // soft delete — the row and its audit trail stay recoverable.
        abort_unless($request->user()->isGroupAdmin(), 403);

        $employee->delete();

        return redirect()
            ->route('employees.index', ['company_id' => $employee->company_id])
            ->with('status', 'تم حذف ملف الموظف (حذف منطقي قابل للاستعادة من قاعدة البيانات).');
    }

    private function formOptions(Request $request): array
    {
        $user = $request->user();
        $companyIds = $user->isGroupAdmin()
            ? Company::pluck('id')
            : $user->companies()->pluck('companies.id');

        return [
            'companies' => Company::whereIn('id', $companyIds)->orderBy('name_ar')->get(),
            'branches' => Branch::whereIn('company_id', $companyIds)->orderBy('name_ar')->get(),
            'departments' => Department::whereHas('branch', fn ($query) => $query->whereIn('company_id', $companyIds))->orderBy('name_ar')->get(),
            'positions' => Position::orderBy('title_ar')->get(),
            'shifts' => Shift::orderBy('name_ar')->get(),
            'managers' => Employee::whereIn('company_id', $companyIds)
                ->employed()
                ->orderBy('name_ar')
                ->get(['id', 'name_ar', 'employee_code', 'company_id']),
        ];
    }
}
