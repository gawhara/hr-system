<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Tests\TestCase;

class EmployeeSpreadsheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_admin_can_export_employee_spreadsheet_csv(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();
        $employee = Employee::where('company_id', 1)->firstOrFail();

        $response = $this->actingAs($admin)->get(route('employees.export', ['company_id' => 1]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('employee_code', $csv);
        $this->assertStringContainsString('full_name_arabic', $csv);
        $this->assertStringContainsString($employee->employee_code, $csv);
        $this->assertStringContainsString($employee->name_ar, $csv);
    }

    public function test_hr_admin_can_export_employee_spreadsheet_xlsx(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();
        $employee = Employee::where('company_id', 1)->firstOrFail();

        $response = $this->actingAs($admin)->get(route('employees.export', ['company_id' => 1, 'format' => 'xlsx']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $rows = $this->readXlsxRows($response->baseResponse->getFile()->getPathname());

        $this->assertContains('employee_code', $rows[0]);
        $this->assertTrue(collect($rows)->contains(fn ($row) => in_array($employee->employee_code, $row, true)));
    }

    public function test_hr_admin_can_download_employee_import_template_xlsx(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('employees.import-template', ['company_id' => 1, 'format' => 'xlsx']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $rows = $this->readXlsxRows($response->baseResponse->getFile()->getPathname());

        $this->assertSame('company_id', $rows[0][0]);
        $this->assertContains('financial_employee_id', $rows[0]);
        $this->assertContains('status', $rows[0]);
    }

    public function test_hr_admin_can_import_employee_spreadsheet_csv(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();

        $csv = implode("\n", [
            'employee_code,name_ar,name_en,national_id,saudi_non_saudi,basic_salary,status,financial_employee_id,job_title,branch_text',
            'IMP-001,Imported Arabic Name,Imported English Name,1077777777,saudi,7100,active,FIN-IMP-001,Imported Job,Imported Branch',
        ]);

        $response = $this->actingAs($admin)->post(route('employees.import'), [
            'company_id' => 1,
            'import_file' => $this->csvUpload($csv),
        ]);

        $employee = Employee::whereNationalId('1077777777')->firstOrFail();

        $response->assertRedirect();
        $response->assertSessionHas('status', 'تم استيراد ملف الموظفين بنجاح.');
        $response->assertSessionHas('import_summary.created', 1);
        $this->assertSame(1, $employee->company_id);
        $this->assertSame('IMP-001', $employee->employee_code);
        $this->assertSame('Imported Job', $employee->job_title);
        $this->assertEquals(7100, (float) $employee->basic_salary);
        $this->assertSame($admin->id, $employee->created_by);
    }

    public function test_hr_admin_can_import_employee_spreadsheet_xlsx(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('employees.import'), [
            'company_id' => 1,
            'import_file' => $this->xlsxUpload([
                ['employee_code', 'name_ar', 'name_en', 'national_id', 'saudi_non_saudi', 'basic_salary', 'status', 'financial_employee_id'],
                ['IMP-XLSX-001', 'Imported XLSX Arabic', 'Imported XLSX English', '1066666666', 'saudi', 8100, 'active', 'FIN-XLSX-001'],
            ]),
        ]);

        $employee = Employee::whereNationalId('1066666666')->firstOrFail();

        $response->assertRedirect();
        $response->assertSessionHas('import_summary.created', 1);
        $this->assertSame('IMP-XLSX-001', $employee->employee_code);
        $this->assertEquals(8100, (float) $employee->basic_salary);
    }

    public function test_import_rejects_duplicate_national_id_without_creating_rows(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();
        $existing = Employee::firstOrFail();

        $csv = implode("\n", [
            'employee_code,name_ar,national_id,saudi_non_saudi,basic_salary,status',
            'IMP-DUP,Duplicate Employee,'.$existing->national_id.',saudi,5000,active',
        ]);

        $this->actingAs($admin)->post(route('employees.import'), [
            'company_id' => $existing->company_id,
            'import_file' => $this->csvUpload($csv),
        ])->assertSessionHasErrors('import_file');

        $this->assertFalse(Employee::where('employee_code', 'IMP-DUP')->exists());
    }

    public function test_employee_role_cannot_import_or_export_employee_spreadsheets(): void
    {
        $this->seed();

        $user = User::create([
            'name' => 'Employee User',
            'email' => 'spreadsheet.employee@hr.local',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'current_company_id' => 1,
        ]);
        $user->companies()->sync([1]);

        $this->actingAs($user)
            ->get(route('employees.export', ['company_id' => 1]))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('employees.import-template', ['company_id' => 1]))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('employees.import'), [
                'company_id' => 1,
                'import_file' => $this->csvUpload("name_ar\nBlocked"),
            ])
            ->assertForbidden();
    }

    public function test_employee_index_shows_import_export_controls_to_hr_admin(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('employees.index', ['company_id' => 1]))
            ->assertOk()
            ->assertSee('تصدير Excel')
            ->assertSee('قالب الاستيراد')
            ->assertSee('اختيار ملف')
            ->assertSee('استيراد');
    }

    private function csvUpload(string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'employees-import-');
        file_put_contents($path, $content);

        return new UploadedFile($path, 'employees.csv', 'text/csv', null, true);
    }

    private function xlsxUpload(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'employees-import-');
        unlink($path);
        $path .= '.xlsx';

        $writer = new XlsxWriter;
        $writer->openToFile($path);

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return new UploadedFile($path, 'employees.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function readXlsxRows(string $path): array
    {
        $reader = new XlsxReader;
        $reader->open($path);

        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = array_map(fn ($cell) => $cell->getValue(), $row->getCells());
            }

            break;
        }

        $reader->close();

        return $rows;
    }
}
