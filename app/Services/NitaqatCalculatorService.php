<?php

namespace App\Services;

use App\Models\EconomicActivity;
use App\Models\Employee;
use App\Models\NitaqatSetting;

class NitaqatCalculatorService
{
    public function employeePayload(Employee $employee): array
    {
        $gosiSalary = (float) $employee->gosi_basic_salary + (float) $employee->gosi_housing_allowance;

        return [
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'is_saudi' => $employee->saudi_non_saudi === 'saudi',
            'employment_type' => $employee->contract_type === 'part_time' ? 'part' : 'full',
            'monthly_salary' => $gosiSalary > 0 ? $gosiSalary : $employee->total_salary,
            'has_disability' => false,
            'is_female' => $employee->gender === 'female',
            'qualification_level' => null,
            'tenure_months' => $employee->contract_start_date ? (int) $employee->contract_start_date->diffInMonths(now()) : 0,
        ];
    }

    public function calculateEmployeeWeight(array $employee): float
    {
        if (empty($employee['is_saudi'])) {
            return 0.0;
        }

        $minSalaryFull = NitaqatSetting::getFloat('min_monthly_salary_for_full_weight', 4000);
        $partialThreshold = NitaqatSetting::getFloat('partial_salary_threshold', 3000);
        $salary = (float) ($employee['monthly_salary'] ?? 0);

        if ($salary < $partialThreshold) {
            return 0.0;
        }

        $baseWeight = ($employee['employment_type'] ?? 'full') === 'part'
            ? NitaqatSetting::getFloat('part_time_weight', 0.5)
            : 1.0;

        if ($salary < $minSalaryFull) {
            $baseWeight *= $salary / $minSalaryFull;
        }

        $weight = $baseWeight;

        if (! empty($employee['has_disability'])) {
            $weight *= NitaqatSetting::getFloat('disability_weight_multiplier', 1);
        }

        if (! empty($employee['is_female'])) {
            $weight += NitaqatSetting::getFloat('female_weight_bonus', 0);
        }

        if (! empty($employee['qualification_level']) && in_array($employee['qualification_level'], ['master', 'phd'], true)) {
            $weight += NitaqatSetting::getFloat('higher_qualification_weight_bonus', 0);
        }

        $tenureBonusAfter = NitaqatSetting::getFloat('tenure_bonus_after_months', 0);
        if ($tenureBonusAfter > 0 && ($employee['tenure_months'] ?? 0) >= $tenureBonusAfter) {
            $weight += NitaqatSetting::getFloat('tenure_weight_bonus', 0);
        }

        return round($weight, 4);
    }

    /**
     * @param  array<int, array<string, mixed>>  $employees
     */
    public function calculateEstablishment(array $employees, EconomicActivity $activity): array
    {
        $totalEmployees = count($employees);
        $totalSaudisHeadcount = 0;
        $totalWeightedSaudis = 0.0;
        $breakdown = [];

        foreach ($employees as $index => $employee) {
            $weight = $this->calculateEmployeeWeight($employee);
            $totalWeightedSaudis += $weight;

            if (! empty($employee['is_saudi'])) {
                $totalSaudisHeadcount++;
            }

            $breakdown[] = [
                'index' => $index,
                'employee_id' => $employee['employee_id'] ?? null,
                'employee_code' => $employee['employee_code'] ?? null,
                'is_saudi' => (bool) ($employee['is_saudi'] ?? false),
                'weight' => $weight,
            ];
        }

        $achievedPercentage = $totalEmployees > 0
            ? round(($totalWeightedSaudis / $totalEmployees) * 100, 4)
            : 0.0;

        $requiredPercentage = round($activity->currentTargetPercentage() * 100, 4);

        return [
            'total_employees' => $totalEmployees,
            'total_saudis_headcount' => $totalSaudisHeadcount,
            'total_weighted_saudis' => round($totalWeightedSaudis, 4),
            'achieved_percentage' => $achievedPercentage,
            'required_percentage' => $requiredPercentage,
            'band' => $this->resolveBand($achievedPercentage, $requiredPercentage),
            'additional_saudis_needed' => $this->estimateAdditionalSaudisNeeded($totalWeightedSaudis, $totalEmployees, $requiredPercentage),
            'breakdown' => $breakdown,
        ];
    }

    private function resolveBand(float $achieved, float $required): string
    {
        if ($required <= 0) {
            return 'unknown';
        }

        $ratio = $achieved / $required;

        return match (true) {
            $ratio >= 1.6 => 'platinum',
            $ratio >= 1.3 => 'high_green',
            $ratio >= 1.0 => 'low_green',
            $ratio >= 0.7 => 'yellow',
            default => 'red',
        };
    }

    private function estimateAdditionalSaudisNeeded(float $totalWeightedSaudis, int $totalEmployees, float $requiredPercentage): int
    {
        if ($totalEmployees === 0) {
            return 0;
        }

        $requiredWeighted = ($requiredPercentage / 100) * $totalEmployees;
        $gap = $requiredWeighted - $totalWeightedSaudis;

        return $gap > 0 ? (int) ceil($gap) : 0;
    }
}
