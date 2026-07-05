<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\Response;

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

    public function export(Request $request): Response
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

        if ($request->query('format') === 'xlsx') {
            $filename = 'employees_'.now()->format('Ymd_His').'.xlsx';
            $path = $this->writeXlsx($filename, function (XlsxWriter $writer) use ($companyIds, $filters, $search) {
                $writer->addRow(Row::fromValues(self::COLUMNS));

                $this->employeeExportQuery($companyIds, $filters, $search)
                    ->each(function (Employee $employee) use ($writer) {
                        $writer->addRow(Row::fromValues($this->employeeExportRow($employee)));
                    });
            });

            return response()->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        $filename = 'employees_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($companyIds, $filters, $search) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::COLUMNS);

            $this->employeeExportQuery($companyIds, $filters, $search)
                ->each(function (Employee $employee) use ($out) {
                    fputcsv($out, $this->employeeExportRow($employee));
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function template(Request $request): Response
    {
        abort_unless($request->user()->can('manage-employees'), 403);

        $companyId = $request->integer('company_id') ?: null;

        if ($companyId) {
            abort_unless($request->user()->canAccessCompany($companyId), 403);
        }

        if ($request->query('format') === 'xlsx') {
            $filename = 'employee_import_template.xlsx';
            $path = $this->writeXlsx($filename, function (XlsxWriter $writer) {
                $writer->addRow(Row::fromValues(self::COLUMNS));
            });

            return response()->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::COLUMNS);
            fclose($out);
        }, 'employee_import_template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(Request $request)
    {
        abort_unless($request->user()->can('manage-employees'), 403);

        $data = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'import_file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
        ]);

        $defaultCompanyId = (int) ($data['company_id'] ?? 0);

        if ($defaultCompanyId) {
            abort_unless($request->user()->canAccessCompany($defaultCompanyId), 403);
        }

        [$rows, $parseErrors] = $this->readSpreadsheet($data['import_file']);

        if ($parseErrors !== []) {
            return back()
                ->withErrors(['import_file' => 'تعذر قراءة ملف الاستيراد.'])
                ->with('import_errors', $parseErrors)
                ->withInput();
        }

        if ($rows === []) {
            return back()
                ->withErrors(['import_file' => 'ملف الاستيراد لا يحتوي على أي صفوف بيانات.'])
                ->withInput();
        }

        $validatedRows = [];
        $rowErrors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $row = $this->prepareImportRow($row, $defaultCompanyId);
            $companyId = (int) ($row['company_id'] ?? 0);

            if (! $companyId || ! $request->user()->canAccessCompany($companyId)) {
                $rowErrors[] = "الصف {$line}: الشركة غير محددة أو غير متاحة لصلاحياتك.";

                continue;
            }

            $validator = Validator::make($row, $this->rules($row));

            if ($validator->fails()) {
                $rowErrors[] = "الصف {$line}: ".$validator->errors()->first();

                continue;
            }

            $validatedRows[] = $validator->validated() + ['created_by' => $request->user()->id];
        }

        if ($rowErrors !== []) {
            return back()
                ->withErrors(['import_file' => 'لم يتم استيراد الملف بسبب وجود أخطاء في البيانات.'])
                ->with('import_errors', array_slice($rowErrors, 0, 8))
                ->with('import_error_count', count($rowErrors))
                ->withInput();
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

        return back()
            ->with('status', 'تم استيراد ملف الموظفين بنجاح.')
            ->with('import_summary', [
                'created' => count($validatedRows),
                'file' => $data['import_file']->getClientOriginalName(),
            ]);
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

    private function employeeExportQuery($companyIds, array $filters, string $search)
    {
        return Employee::with(['company', 'branch', 'department', 'position', 'shift', 'manager'])
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
            ->orderBy('employee_code');
    }

    private function employeeExportRow(Employee $employee): array
    {
        return array_map(fn ($column) => $this->exportValue($employee, $column), self::COLUMNS);
    }

    private function writeXlsx(string $filename, callable $callback): string
    {
        $path = tempnam(storage_path('app'), pathinfo($filename, PATHINFO_FILENAME).'_');
        unlink($path);
        $path .= '.xlsx';

        $writer = new XlsxWriter;
        $writer->openToFile($path);
        $callback($writer);
        $writer->close();

        return $path;
    }

    private function exportValue(Employee $employee, string $column): mixed
    {
        $value = $employee->{$column};

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return $value;
    }

    private function readSpreadsheet(UploadedFile $file): array
    {
        if (strtolower($file->getClientOriginalExtension()) === 'xlsx') {
            return $this->readXlsx($file);
        }

        return $this->readCsv($file);
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

    private function readXlsx(UploadedFile $file): array
    {
        $reader = new XlsxReader;
        $reader->open($file->getRealPath());

        $header = null;
        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $xlsxRow) {
                $values = array_map(fn ($cell) => $this->normalizeCellValue($cell->getValue()), $xlsxRow->getCells());

                if ($rowIndex === 1) {
                    $header = array_map(fn ($column) => $this->normalizeHeader((string) $column), $values);
                    $unknownColumns = array_diff($header, self::COLUMNS);

                    if ($unknownColumns !== []) {
                        $reader->close();

                        return [[], ['Unknown columns: '.implode(', ', $unknownColumns).'.']];
                    }

                    continue;
                }

                if (collect($values)->every(fn ($value) => trim((string) $value) === '')) {
                    continue;
                }

                $row = [];

                foreach ($header ?? [] as $index => $column) {
                    $row[$column] = $values[$index] ?? null;
                }

                $rows[] = $row;
            }

            break;
        }

        $reader->close();

        if ($header === null) {
            return [[], ['The uploaded file is empty.']];
        }

        return [$rows, []];
    }

    private function normalizeCellValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return $value;
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
