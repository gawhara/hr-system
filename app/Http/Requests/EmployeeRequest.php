<?php

namespace App\Http\Requests;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-employees')
            && $this->user()->canAccessCompany((int) $this->input('company_id'));
    }

    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id' => [
                'nullable',
                'exists:branches,id',
                function ($attribute, $value, $fail) {
                    if ($value && Branch::find($value)?->company_id !== (int) $this->input('company_id')) {
                        $fail(__('الفرع المحدد لا يتبع الشركة المختارة.'));
                    }
                },
            ],
            'department_id' => [
                'nullable',
                'exists:departments,id',
                function ($attribute, $value, $fail) {
                    if ($value && Department::find($value)?->branch_id !== (int) $this->input('branch_id')) {
                        $fail(__('القسم المحدد لا يتبع الفرع المختار.'));
                    }
                },
            ],
            'position_id' => ['nullable', 'exists:positions,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'branch_text' => ['nullable', 'string', 'max:100'],
            'employee_code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('employees', 'employee_code')->ignore($employee)->withoutTrashed(),
            ],
            'financial_employee_id' => [
                'nullable', 'string', 'max:50',
                Rule::unique('employees', 'financial_employee_id')->ignore($employee)->withoutTrashed(),
            ],
            // Enrollment id on the ZK device; punches map to employees by
            // (device company, this id) — unique inside each company.
            'biometric_user_id' => [
                'nullable', 'string', 'max:50',
                Rule::unique('employees', 'biometric_user_id')
                    ->where('company_id', (int) $this->input('company_id'))
                    ->ignore($employee)
                    ->withoutTrashed(),
            ],
            'hr_employee_id' => [
                'nullable', 'string', 'max:50',
                Rule::unique('employees', 'hr_employee_id')->ignore($employee)->withoutTrashed(),
            ],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'full_name_arabic' => ['nullable', 'string', 'max:255'],
            'full_name_english' => ['nullable', 'string', 'max:255'],
            'iqama_full_name_arabic' => ['nullable', 'string', 'max:255'],
            'iqama_full_name_english' => ['nullable', 'string', 'max:255'],
            'passport_full_name_arabic' => ['nullable', 'string', 'max:255'],
            'passport_full_name_english' => ['nullable', 'string', 'max:255'],
            // Encrypted at rest: uniqueness is enforced via national_id_hash.
            'national_id' => [
                'nullable', 'digits:10',
                function ($attribute, $value, $fail) use ($employee) {
                    if ($value === null) {
                        return;
                    }

                    $exists = Employee::whereNationalId($value)
                        ->when($employee, fn ($query) => $query->whereKeyNot($employee->id))
                        ->exists();

                    if ($exists) {
                        $fail(__('رقم الهوية / الإقامة مسجل لموظف آخر.'));
                    }
                },
            ],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('employees', 'email')->ignore($employee)->withoutTrashed(),
            ],
            'phone' => [
                'nullable', 'string', 'max:10',
                Rule::unique('employees', 'phone')->ignore($employee)->withoutTrashed(),
            ],
            'phone_2' => [
                'nullable', 'string', 'max:10',
                Rule::unique('employees', 'phone_2')->ignore($employee)->withoutTrashed(),
            ],
            'nationality' => ['nullable', 'string', 'max:100'],
            'saudi_non_saudi' => ['required', Rule::in(['saudi', 'non_saudi'])],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            // PRD §7.2: employee must be at least 15 years old.
            'birth_date' => ['nullable', 'date', 'before_or_equal:' . now()->subYears(15)->toDateString()],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed', 'other'])],
            'address' => ['nullable', 'string', 'max:1000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'work_location' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'manager_id' => [
                'nullable', 'exists:employees,id',
                function ($attribute, $value, $fail) use ($employee) {
                    if ($employee && (int) $value === $employee->id) {
                        $fail(__('لا يمكن أن يكون الموظف مديراً لنفسه.'));

                        return;
                    }

                    $manager = Employee::find($value);

                    if ($manager && $manager->company_id !== (int) $this->input('company_id')) {
                        $fail(__('المدير المباشر يجب أن يكون من نفس الشركة.'));
                    }
                },
            ],
            'iqama_expiry' => ['nullable', 'date'],
            'passport_id' => ['nullable', 'string', 'max:50'],
            'passport_expiry' => ['nullable', 'date'],
            'contract_type' => ['nullable', Rule::in(Employee::CONTRACT_TYPES)],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after:contract_start_date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'probation_end_date' => ['nullable', 'date', 'after:contract_start_date'],
            'bank_name' => ['nullable', 'string', 'max:100', 'required_with:iban'],
            'bank' => ['nullable', 'string', 'max:100'],
            'iban' => ['nullable', 'string', 'max:34', 'regex:/^SA\d{22}$/'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'overtime' => ['nullable', 'numeric', 'min:0'],
            'housing_allowance' => ['nullable', 'numeric', 'min:0'],
            'transportation_allowance' => ['nullable', 'numeric', 'min:0'],
            'other_allowances' => ['nullable', 'numeric', 'min:0'],
            'training_labor_wages' => ['nullable', 'numeric', 'min:0'],
            'previous_dues' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'gosi_basic_salary' => ['nullable', 'numeric', 'min:0'],
            'gosi_housing_allowance' => ['nullable', 'numeric', 'min:0'],
            'basic_salary_gosi' => ['nullable', 'numeric', 'min:0'],
            'housing_allowance_gosi' => ['nullable', 'numeric', 'min:0'],
            'other_gosi_items' => ['nullable', 'numeric', 'min:0'],
            'diff_registered_housing_allowance' => ['nullable', 'numeric'],
            'absence_deduction' => ['nullable', 'numeric', 'min:0'],
            'delay_deduction' => ['nullable', 'numeric', 'min:0'],
            'leave_deduction' => ['nullable', 'numeric', 'min:0'],
            'warnings_penalties' => ['nullable', 'numeric', 'min:0'],
            'insurance_deduction' => ['nullable', 'numeric', 'min:0'],
            'loans' => ['nullable', 'numeric', 'min:0'],
            'social_insurance_saudi' => ['nullable', 'numeric', 'min:0'],
            'total_deductions' => ['nullable', 'numeric', 'min:0'],
            'cash' => ['nullable', 'numeric', 'min:0'],
            'al_rajhi_transfer' => ['nullable', 'numeric', 'min:0'],
            'bank_albilad_transfer' => ['nullable', 'numeric', 'min:0'],
            'riyad_bank_transfer' => ['nullable', 'numeric', 'min:0'],
            'remaining_salary' => ['nullable', 'numeric'],
            'employment_status' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(Employee::STATUSES)],
        ];
    }

    public function attributes(): array
    {
        return [
            'company_id' => 'الشركة',
            'branch_id' => 'الفرع',
            'department_id' => 'القسم',
            'position_id' => 'المسمى الوظيفي',
            'name_ar' => 'الاسم بالعربية',
            'national_id' => 'رقم الهوية / الإقامة',
            'saudi_non_saudi' => 'التصنيف (سعودي / غير سعودي)',
            'basic_salary' => 'الراتب الأساسي',
            'iban' => 'الآيبان',
            'contract_end_date' => 'تاريخ نهاية العقد',
        ];
    }
}
