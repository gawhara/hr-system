<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\GosiSetting;

/**
 * All GOSI math flows through here so rates/caps always come from
 * gosi_settings (per company, with group-wide defaults) — never constants.
 * The eligible-wage definition (basic + housing) has changed historically;
 * keep it in this one place.
 */
class GosiCalculatorService
{
    public function eligibleWage(Employee $employee): float
    {
        $wage = (float) $employee->gosi_basic_salary + (float) $employee->gosi_housing_allowance;
        $cap = GosiSetting::valueFor('max_gosi_wage', $employee->company_id);

        return $cap !== null ? min($wage, $cap) : $wage;
    }

    public function employeeContribution(Employee $employee): float
    {
        $rate = $employee->saudi_non_saudi === 'saudi'
            ? GosiSetting::valueFor('saudi_employee_rate', $employee->company_id, 0.0)
            : GosiSetting::valueFor('non_saudi_employee_rate', $employee->company_id, 0.0);

        return round($this->eligibleWage($employee) * $rate, 2);
    }

    public function employerContribution(Employee $employee): float
    {
        $rate = $employee->saudi_non_saudi === 'saudi'
            ? GosiSetting::valueFor('saudi_employer_rate', $employee->company_id, 0.0)
            : GosiSetting::valueFor('non_saudi_employer_rate', $employee->company_id, 0.0);

        return round($this->eligibleWage($employee) * $rate, 2);
    }
}
