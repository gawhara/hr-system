<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContractController extends Controller
{
    public function create(Request $request, Employee $employee)
    {
        $this->authorizeManage($request, $employee);

        return view('contracts.create', [
            'employee' => $employee,
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        $this->authorizeManage($request, $employee);

        $data = $request->validate([
            'contract_number' => ['nullable', 'string', 'max:100'],
            'contract_type' => ['required', Rule::in(\App\Models\Employee::CONTRACT_TYPES)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after:starts_on', 'required_if:contract_type,fixed'],
            'probation_ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'housing_allowance' => ['nullable', 'numeric', 'min:0'],
            'transportation_allowance' => ['nullable', 'numeric', 'min:0'],
            'other_allowances' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Contracts always belong to the employee's legal entity: a
        // cross-company move is a new contract under the other company plus
        // a termination here — never an edit (AGENT.md).
        $employee->contracts()->create($data + [
            'company_id' => $employee->company_id,
            'status' => 'active',
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', 'تم إنشاء العقد بنجاح.');
    }

    public function terminate(Request $request, Contract $contract)
    {
        $this->authorizeManage($request, $contract->employee);

        $data = $request->validate([
            'termination_reason' => ['required', 'string', 'max:2000'],
        ]);

        abort_if($contract->status === 'terminated', 422, 'العقد منتهي بالفعل.');

        $contract->update([
            'status' => 'terminated',
            'terminated_at' => now(),
            'termination_reason' => $data['termination_reason'],
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('employees.show', $contract->employee)
            ->with('status', 'تم إنهاء العقد.');
    }

    private function authorizeManage(Request $request, Employee $employee): void
    {
        abort_unless($request->user()->can('manage-employees'), 403);
        abort_unless($request->user()->canAccessCompany($employee->company_id), 403);
    }
}
