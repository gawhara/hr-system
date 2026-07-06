<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Group;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollCycle;
use App\Models\PayrollItem;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use App\Services\GosiCalculatorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(NitaqatSettingsSeeder::class);
        $this->call(GosiSettingsSeeder::class);
        $this->call(DocumentTypesSeeder::class);

        $group = Group::create([
            'name_ar' => 'مجموعة أمنيات وتقنيات الدهان',
            'name_en' => 'AMNIAT & PTC Group',
        ]);

        $companies = collect([
            ['name_ar' => 'أمنيات للأمن و السلامة', 'name_en' => 'AMNIAT', 'cr_number' => '1010000001', 'gosi_number' => 'GOSI-1001'],
            ['name_ar' => 'مصنع امنيات', 'name_en' => 'AMNIAT Factory', 'cr_number' => '4030000002', 'gosi_number' => 'GOSI-2002'],
            ['name_ar' => 'تقنيات الدهان للتجارة', 'name_en' => 'PTC', 'cr_number' => '2050000003', 'gosi_number' => 'GOSI-3003'],
            ['name_ar' => 'تقنيات الدهان للمقاولات', 'name_en' => 'PTC Construction', 'cr_number' => '4650000004', 'gosi_number' => 'GOSI-4004'],
        ])->map(fn ($data) => Company::create($data + [
            'group_id' => $group->id,
            'address_ar' => 'المملكة العربية السعودية',
            'address_en' => 'Saudi Arabia',
        ]));

        $positions = collect([
            ['title_ar' => 'مدير الموارد البشرية', 'title_en' => 'HR Manager', 'job_grade' => 'M2'],
            ['title_ar' => 'محاسب رواتب', 'title_en' => 'Payroll Accountant', 'job_grade' => 'S2'],
            ['title_ar' => 'مشرف عمليات', 'title_en' => 'Operations Supervisor', 'job_grade' => 'S3'],
            ['title_ar' => 'أخصائي دعم', 'title_en' => 'Support Specialist', 'job_grade' => 'S1'],
            ['title_ar' => 'مندوب مبيعات', 'title_en' => 'Sales Representative', 'job_grade' => 'S1'],
        ])->map(fn ($data) => Position::create($data));

        $shift = Shift::create([
            'name_ar' => 'دوام إداري',
            'name_en' => 'Office Shift',
            'starts_at' => '08:00',
            'ends_at' => '17:00',
            'grace_minutes' => 15,
        ]);

        $superAdmin = User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@hr.local',
            'password' => Hash::make(env('HR_SEED_PASSWORD', 'password')),
            'role' => 'group_admin',
            'current_company_id' => $companies->first()->id,
        ]);
        $superAdmin->companies()->sync($companies->pluck('id'));

        $leaveTypes = collect([
            ['name_ar' => 'إجازة سنوية', 'name_en' => 'Annual Leave', 'default_days' => 21],
            ['name_ar' => 'إجازة مرضية', 'name_en' => 'Sick Leave', 'default_days' => 30],
            ['name_ar' => 'إجازة طارئة', 'name_en' => 'Emergency Leave', 'default_days' => 5],
        ])->map(fn ($data) => LeaveType::create($data));

        $employeeCounter = 1;

        foreach ($companies as $companyIndex => $company) {
            // Demo device rows: inactive placeholders so the scheduled pull
            // never dials fake addresses — activate real devices from /devices.
            \App\Models\BiometricDevice::create([
                'company_id' => $company->id,
                'name_ar' => 'بصمة الفرع الرئيسي',
                'name_en' => 'Main Branch Device',
                'host' => sprintf('192.168.%d.201', 10 + $companyIndex),
                'port' => 4370,
                'is_active' => false,
            ]);

            $hrUser = User::create([
                'name' => 'مسؤول موارد ' . ($companyIndex + 1),
                'email' => 'hr' . ($companyIndex + 1) . '@hr.local',
                'password' => Hash::make(env('HR_SEED_PASSWORD', 'password')),
                'role' => 'hr_manager',
                'current_company_id' => $company->id,
            ]);
            $hrUser->companies()->sync([$company->id]);

            foreach ([
                ['name_ar' => 'الفرع الرئيسي', 'name_en' => 'Main Branch', 'city_ar' => 'الرياض', 'city_en' => 'Riyadh'],
                ['name_ar' => 'فرع المنطقة الغربية', 'name_en' => 'Western Branch', 'city_ar' => 'جدة', 'city_en' => 'Jeddah'],
            ] as $branchData) {
                $branch = Branch::create($branchData + ['company_id' => $company->id]);

                foreach ([
                    ['name_ar' => 'الموارد البشرية', 'name_en' => 'Human Resources'],
                    ['name_ar' => 'المالية', 'name_en' => 'Finance'],
                    ['name_ar' => 'العمليات', 'name_en' => 'Operations'],
                ] as $departmentData) {
                    Department::create($departmentData + ['branch_id' => $branch->id]);
                }
            }

            $departments = Department::whereHas('branch', fn ($query) => $query->where('company_id', $company->id))->get();

            for ($i = 0; $i < 6; $i++) {
                $department = $departments[$i % $departments->count()];
                $branch = $department->branch;
                $position = $positions[$i % $positions->count()];
                $isSaudi = $i % 3 === 0;
                $basicSalary = 5000 + ($i * 650) + ($companyIndex * 300);

                $employee = Employee::create([
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'department_id' => $department->id,
                    'position_id' => $position->id,
                    'shift_id' => $shift->id,
                    'user_id' => $i === 0 ? $hrUser->id : null,
                    'employee_code' => sprintf('EMP-%03d', $employeeCounter),
                    'financial_employee_id' => sprintf('FIN-%03d', $employeeCounter),
                    'hr_employee_id' => sprintf('HR-%03d', $employeeCounter),
                    'biometric_user_id' => (string) $employeeCounter,
                    'national_id' => (string) (1000000000 + $employeeCounter),
                    'name_ar' => 'موظف تجريبي ' . $employeeCounter,
                    'name_en' => 'Demo Employee ' . $employeeCounter,
                    'email' => 'employee' . $employeeCounter . '@hr.local',
                    'phone' => '05' . str_pad((string) $employeeCounter, 8, '0', STR_PAD_LEFT),
                    'nationality' => $isSaudi ? 'Saudi' : 'Egyptian',
                    'saudi_non_saudi' => $isSaudi ? 'saudi' : 'non_saudi',
                    'gender' => $i % 2 === 0 ? 'male' : 'female',
                    'birth_date' => Carbon::now()->subYears(28 + $i)->toDateString(),
                    'iqama_expiry' => $isSaudi ? null : Carbon::now()->addDays(30 + ($i * 12))->toDateString(),
                    'passport_id' => 'P' . (800000 + $employeeCounter),
                    'passport_expiry' => Carbon::now()->addMonths(8 + $i)->toDateString(),
                    'contract_type' => $i % 2 === 0 ? 'fixed' : 'indefinite',
                    'contract_start_date' => Carbon::now()->subMonths(10 + $i)->toDateString(),
                    'contract_end_date' => $i % 2 === 0 ? Carbon::now()->addMonths(14 - $i)->toDateString() : null,
                    'bank_name' => ['Al Rajhi Bank', 'Bank Albilad', 'Riyad Bank'][$i % 3],
                    'iban' => 'SA' . str_pad((string) (1000000000000000000000 + $employeeCounter), 22, '0', STR_PAD_LEFT),
                    'basic_salary' => $basicSalary,
                    'housing_allowance' => $basicSalary * 0.25,
                    'transportation_allowance' => 750,
                    'other_allowances' => $i * 100,
                    'gosi_basic_salary' => $basicSalary,
                    'gosi_housing_allowance' => $basicSalary * 0.25,
                    'status' => 'active',
                    'created_by' => $superAdmin->id,
                ]);

                $employee->contracts()->create([
                    'company_id' => $company->id,
                    'contract_number' => 'CT-' . str_pad((string) $employeeCounter, 4, '0', STR_PAD_LEFT),
                    'contract_type' => $employee->contract_type,
                    'starts_on' => $employee->contract_start_date,
                    'ends_on' => $employee->contract_end_date,
                    'basic_salary' => $employee->basic_salary,
                    'housing_allowance' => $employee->housing_allowance,
                    'transportation_allowance' => $employee->transportation_allowance,
                    'other_allowances' => $employee->other_allowances,
                    'status' => 'active',
                    'created_by' => $superAdmin->id,
                ]);

                if (! $isSaudi) {
                    $employee->documents()->create([
                        'document_type_id' => \App\Models\DocumentType::where('key', 'iqama')->value('id'),
                        'document_number' => $employee->national_id,
                        'expiry_date' => $employee->iqama_expiry,
                        'created_by' => $superAdmin->id,
                    ]);
                }

                if ($i === 2) {
                    $employee->documents()->create([
                        'document_type_id' => \App\Models\DocumentType::where('key', 'medical_insurance')->value('id'),
                        'document_number' => 'INS-' . (5000 + $employeeCounter),
                        'expiry_date' => Carbon::now()->addDays(20)->toDateString(),
                        'created_by' => $superAdmin->id,
                    ]);
                }

                foreach ($leaveTypes as $leaveType) {
                    $employee->leaveBalances()->create([
                        'leave_type_id' => $leaveType->id,
                        'year' => (int) now()->format('Y'),
                        'entitled_days' => $leaveType->default_days,
                        'used_days' => 0,
                    ]);
                }

                AttendanceRecord::create([
                    'employee_id' => $employee->id,
                    'work_date' => now()->toDateString(),
                    'check_in' => $i === 5 ? null : ($i % 2 === 0 ? '08:03' : '08:24'),
                    'check_out' => $i === 5 ? null : '17:00',
                    'status' => $i === 5 ? 'absent' : 'present',
                    'late_minutes' => $i === 5 ? 0 : ($i % 2 === 0 ? 0 : 9),
                    'absence_minutes' => $i === 5 ? 480 : 0,
                    'notes' => $i === 5 ? 'غياب تجريبي' : null,
                ]);

                if ($i === 1) {
                    LeaveRequest::create([
                        'employee_id' => $employee->id,
                        'leave_type_id' => $leaveTypes->first()->id,
                        'starts_on' => now()->addDays(5)->toDateString(),
                        'ends_on' => now()->addDays(7)->toDateString(),
                        'days' => 3,
                        'status' => 'pending',
                        'reason' => 'طلب إجازة تجريبي',
                    ]);
                }

                $employeeCounter++;
            }

            $cycle = PayrollCycle::create([
                'company_id' => $company->id,
                'year' => (int) now()->format('Y'),
                'month' => (int) now()->format('m'),
                'period_starts_on' => now()->startOfMonth()->toDateString(),
                'period_ends_on' => now()->endOfMonth()->toDateString(),
            ]);

            $gosiCalculator = app(GosiCalculatorService::class);

            Employee::where('company_id', $company->id)->each(function (Employee $employee) use ($cycle, $gosiCalculator) {
                $gross = $employee->total_salary;
                $gosi = $gosiCalculator->employeeContribution($employee);

                PayrollItem::create([
                    'payroll_cycle_id' => $cycle->id,
                    'employee_id' => $employee->id,
                    'basic_salary' => $employee->basic_salary,
                    'housing_allowance' => $employee->housing_allowance,
                    'transportation_allowance' => $employee->transportation_allowance,
                    'other_allowances' => $employee->other_allowances,
                    'social_insurance_saudi' => $gosi,
                    'gross_total' => $gross,
                    'total_deductions' => $gosi,
                    'net_salary' => $gross - $gosi,
                    'al_rajhi_transfer' => $employee->bank_name === 'Al Rajhi Bank' ? $gross - $gosi : 0,
                    'bank_albilad_transfer' => $employee->bank_name === 'Bank Albilad' ? $gross - $gosi : 0,
                    'riyad_bank_transfer' => $employee->bank_name === 'Riyad Bank' ? $gross - $gosi : 0,
                ]);
            });
        }
    }
}
