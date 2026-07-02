<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\GosiSetting;
use App\Services\GosiCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GosiCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private GosiCalculatorService $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\GosiSettingsSeeder::class);
        $this->company = Company::create(['name_ar' => 'شركة', 'name_en' => 'Co']);
        $this->calculator = app(GosiCalculatorService::class);
    }

    private function makeEmployee(array $attributes): Employee
    {
        return Employee::create($attributes + [
            'company_id' => $this->company->id,
            'name_ar' => 'موظف',
        ]);
    }

    public function test_saudi_contribution_uses_configured_rate_on_eligible_wage(): void
    {
        $employee = $this->makeEmployee([
            'saudi_non_saudi' => 'saudi',
            'gosi_basic_salary' => 8000,
            'gosi_housing_allowance' => 2000,
        ]);

        $rate = GosiSetting::valueFor('saudi_employee_rate');

        $this->assertSame(round(10000 * $rate, 2), $this->calculator->employeeContribution($employee));
    }

    public function test_non_saudi_employee_pays_nothing_but_employer_pays_hazards(): void
    {
        $employee = $this->makeEmployee([
            'saudi_non_saudi' => 'non_saudi',
            'gosi_basic_salary' => 6000,
            'gosi_housing_allowance' => 1500,
        ]);

        $this->assertSame(0.0, $this->calculator->employeeContribution($employee));

        $employerRate = GosiSetting::valueFor('non_saudi_employer_rate');
        $this->assertSame(round(7500 * $employerRate, 2), $this->calculator->employerContribution($employee));
    }

    public function test_eligible_wage_is_capped_at_configured_maximum(): void
    {
        $employee = $this->makeEmployee([
            'saudi_non_saudi' => 'saudi',
            'gosi_basic_salary' => 50000,
            'gosi_housing_allowance' => 10000,
        ]);

        $this->assertSame(GosiSetting::valueFor('max_gosi_wage'), $this->calculator->eligibleWage($employee));
    }

    public function test_company_specific_rate_overrides_group_default(): void
    {
        GosiSetting::create([
            'company_id' => $this->company->id,
            'key' => 'saudi_employee_rate',
            'value' => 0.05,
            'label_ar' => 'نسبة خاصة بالشركة',
        ]);

        $employee = $this->makeEmployee([
            'saudi_non_saudi' => 'saudi',
            'gosi_basic_salary' => 4000,
            'gosi_housing_allowance' => 0,
        ]);

        $this->assertSame(200.0, $this->calculator->employeeContribution($employee));
    }
}
