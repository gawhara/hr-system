<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeSpreadsheetController extends Controller
{
    private const COLUMNS = [
        'company_id',
        'employee_code',
        'financial_employee_id',
        'hr_employee_id',
        'name_ar',
        'name_en',
        'full_name_arabic',
        'full_name_english',
        'iqama_full_name_arabic',
        'iqama_full_name_english',
        'passport_full_name_arabic',
        'passport_full_name_english',
        'national_id',
        'email',
        'phone',
        'phone_2',
        'nationality',
        'saudi_non_saudi',
        'gender',
        'birth_date',
        'marital_status',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'branch_id',
        'branch_text',
        'department_id',
        'position_id',
        'shift_id',
        'job_title',
        'work_location',
        'manager_id',
        'iqama_expiry',
        'passport_id',
        'passport_expiry',
        'contract_type',
        'contract_start_date',
        'contract_end_date',
        'start_date',
        'end_date',
        'probation_end_date',
        'bank_name',
        'bank',
        'iban',
        'basic_salary',
        'overtime',
        'housing_allowance',
        'transportation_allowance',
        'other_allowances',
        'training_labor_wages',
        'previous_dues',
        'total',
        'gosi_basic_salary',
        'gosi_housing_allowance',
        'basic_salary_gosi',
        'housing_allowance_gosi',
        'other_gosi_items',
        'diff_registered_housing_allowance',
        'absence_deduction',
        'delay_deduction',
        'leave_deduction',
        'warnings_penalties',
        'insurance_deduction',
        'loans',
        'social_insurance_saudi',
        'total_deductions',
        'cash',
        'al_rajhi_transfer',
        'bank_albilad_transfer',
        'riyad_bank_transfer',
        'remaining_salary',
        'employment_status',
        'status',
    ];

    private const NUMERIC_COLUMNS = [
        'basic_salary',
        'overtime',
        'housing_allowance',
        'transportation_allowance',
        'other_allowances',
        'training_labor_wages',
        'previous_dues',
        'total',
        'gosi_basic_salary',
        'gosi_housing_allowance',
        'basic_salary_gosi',
        'housing_allowance_gosi',
        'other_gosi_items',
        'diff_registered_housing_allowance',
        'absence_deduction',
        'delay_deduction',
        'leave_deduction',
        'warnings_penalties',
        'insurance_deduction',
        'loans',
        'social_insurance_saudi',
        'total_deductions',
        'cash',
        'al_rajhi_transfer',
        'bank_albilad_transfer',
        'riyad_bank_transfer',
        'remaining_salary',
    ];

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->can('manage-employees'), 403);

        $companyIds = $this->accessibleCompanyIds($request);
        $filters = $this->filters($request);
        $search = trim((string) $request->query('search'));

        if ($filters['company_id']) {
            abort_unless($companyIds->contains($filters['company_id']), 403);
        }

        activity()
            ->causedBy($request->user())
            ->event('employee_export')
            ->log('Employee spreadsheet exported');

        $filename = 'employees_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($companyIds, $filters, $search) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::COLUMNS);

            Employee::with(['company', 'branch', 'department', 'position', 'shift', 'manager'])
                ->whereIn('company_id', $companyIds)
                ->when($filters['company_id'], fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->when($filters['branch_id'], fn ($query, $branchId) => $query->where('branch_id', $branchId))
                ->when($filters['department_id'], fn ($query, $departmentId) => $query->where('department_id', $departmentId))
                ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
                ->when($filters['contract_type'], fn ($query, $contractType) => $query->where('contract_type', $contractType))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('name_ar', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%")
                            ->orWhere('employee_code', 'like', "%{$search}%")
                            ->orWhere('national_id_hash', hash('sha256', $search));
                    });
                })
                ->orderBy('employee_code')
                ->each(function (Employee $employee) use ($out) {
                    fputcsv($out, array_map(fn ($column) => $this->exportValue($employee, $column), self::COLUMNS));
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(Request $request)
    {
        abort_unless($request->user()->can('manage-employees'), 403);

        $data = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'import_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $defaultCompanyId = (int) ($data['company_id'] ?? 0);

        if ($defaultCompanyId) {
            abort_unless($request->user()->canAccessCompany($defaultCompanyId), 403);
        }

        [$rows, $parseErrors] = $this->readCsv($data['import_file']);

        if ($parseErrors !== []) {
            return back()->withErrors(['import_file' => implode(' ', $parseErrors)])->withInput();
        }

        $validatedRows = [];
        $rowErrors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $row = $this->prepareImportRow($row, $defaultCompanyId);
            $companyId = (int) ($row['company_id'] ?? 0);

            if (! $companyId || ! $request->user()->canAccessCompany($companyId)) {
                $rowErrors[] = "Row {$line}: company_id is missing or inaccessible.";

                continue;
            }

            $validator = Validator::make($row, $this->rules($row));

            if ($validator->fails()) {
                $rowErrors[] = "Row {$line}: ".$validator->errors()->first();

                continue;
            }

            $validatedRows[] = $validator->validated() + ['created_by' => $request->user()->id];
        }

        if ($rowErrors !== []) {
            return back()->withErrors(['import_file' => implode(' ', array_slice($rowErrors, 0, 8))])->withInput();
        }

        DB::transaction(function () use ($validatedRows) {
            foreach ($validatedRows as $row) {
                Employee::create($row);
            }
        });

        activity()
            ->causedBy($request->user())
            ->event('employee_import')
            ->withProperties(['rows' => count($validatedRows)])
            ->log('Employee spreadsheet imported');

        return back()->with('status', 'Imported '.count($validatedRows).' employee rows.');
    }

    private function accessibleCompanyIds(Request $request)
    {
        return $request->user()->isGroupAdmin()
            ? Company::pluck('id')
            : $request->user()->companies()->pluck('companies.id');
    }

    private function filters(Request $request): array
    {
        return [
            'company_id' => $request->integer('company_id') ?: null,
            'branch_id' => $request->integer('branch_id') ?: null,
            'department_id' => $request->integer('department_id') ?: null,
            'status' => in_array($request->query('status'), Employee::STATUSES, true) ? $request->query('status') : null,
            'contract_type' => in_array($request->query('contract_type'), Employee::CONTRACT_TYPES, true) ? $request->query('contract_type') : null,
        ];
    }

    private function exportValue(Employee $employee, string $column): mixed
    {
        $value = $employee->{$column};

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return $value;
    }

    private function readCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return [[], ['Unable to read the uploaded file.']];
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            return [[], ['The uploaded file is empty.']];
        }

        $header = array_map(fn ($column) => $this->normalizeHeader((string) $column), $header);
        $unknownColumns = array_diff($header, self::COLUMNS);

        if ($unknownColumns !== []) {
            fclose($handle);

            return [[], ['Unknown columns: '.implode(', ', $unknownColumns).'.']];
        }

        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if ($line === [null] || collect($line)->every(fn ($value) => trim((string) $value) === '')) {
                continue;
            }

            $row = [];

            foreach ($header as $index => $column) {
                $row[$column] = $line[$index] ?? null;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return [$rows, []];
    }

    private function normalizeHeader(string $column): string
    {
        $column = preg_replace('/^\xEF\xBB\xBF/', '', $column);
        $column = strtolower(trim($column));

        return str_replace([' ', '-'], '_', $column);
    }

    private function prepareImportRow(array $row, int $defaultCompanyId): array
    {
        $row = array_intersect_key($row, array_flip(self::COLUMNS));

        foreach (self::COLUMNS as $column) {
            $row[$column] = isset($row[$column]) && trim((string) $row[$column]) !== ''
                ? trim((string) $row[$column])
                : null;
        }

        $row['company_id'] = $row['company_id'] ?: ($defaultCompanyId ?: null);
        $row['status'] = $row['status'] ?: 'active';
        $row['saudi_non_saudi'] = $row['saudi_non_saudi'] ?: 'saudi';
        foreach (self::NUMERIC_COLUMNS as $column) {
            $row[$column] = $row[$column] !== null
                ? str_replace(',', '', $row[$column])
                : 0;
        }

        return $row;
    }

    private function rules(array $row): array
    {
        $companyId = (int) ($row['company_id'] ?? 0);

        return [
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id' => ['nullable', 'exists:branches,id', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'branch_text' => ['nullable', 'string', 'max:100'],
            'employee_code' => ['nullable', 'string', 'max:50', Rule::unique('employees', 'employee_code')->withoutTrashed()],
            'financial_employee_id' => ['nullable', 'string', 'max:50', Rule::unique('employees', 'financial_employee_id')->withoutTrashed()],
            'hr_employee_id' => ['nullable', 'string', 'max:50', Rule::unique('employees', 'hr_employee_id')->withoutTrashed()],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'full_name_arabic' => ['nullable', 'string', 'max:255'],
            'full_name_english' => ['nullable', 'string', 'max:255'],
            'iqama_full_name_arabic' => ['nullable', 'string', 'max:255'],
            'iqama_full_name_english' => ['nullable', 'string', 'max:255'],
            'passport_full_name_arabic' => ['nullable', 'string', 'max:255'],
            'passport_full_name_english' => ['nullable', 'string', 'max:255'],
            'national_id' => ['nullable', 'digits:10', function ($attribute, $value, $fail) {
                if ($value !== null && Employee::whereNationalId($value)->exists()) {
                    $fail('national_id is already assigned to another employee.');
                }
            }],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->withoutTrashed()],
            'phone' => ['nullable', 'string', 'max:10', Rule::unique('employees', 'phone')->withoutTrashed()],
            'phone_2' => ['nullable', 'string', 'max:10', Rule::unique('employees', 'phone_2')->withoutTrashed()],
            'nationality' => ['nullable', 'string', 'max:100'],
            'saudi_non_saudi' => ['required', Rule::in(['saudi', 'non_saudi'])],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'birth_date' => ['nullable', 'date', 'before_or_equal:'.now()->subYears(15)->toDateString()],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed', 'other'])],
            'address' => ['nullable', 'string', 'max:1000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'work_location' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'manager_id' => ['nullable', 'exists:employees,id'],
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
}
