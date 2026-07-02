<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EconomicActivity extends Model
{
    protected $fillable = [
        'isic_code',
        'name_ar',
        'min_establishment_size',
        'target_percentage_year1',
        'target_percentage_year2',
        'target_percentage_year3',
        'plan_effective_date',
        'source_reference',
        'verified_at',
        'is_active',
    ];

    protected $casts = [
        'target_percentage_year1' => 'float',
        'target_percentage_year2' => 'float',
        'target_percentage_year3' => 'float',
        'plan_effective_date' => 'date',
        'verified_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function currentTargetPercentage(): float
    {
        $yearsElapsed = $this->plan_effective_date
            ? (int) $this->plan_effective_date->diffInYears(now())
            : 0;

        if ($yearsElapsed >= 2 && $this->target_percentage_year3 !== null) {
            return $this->target_percentage_year3;
        }

        if ($yearsElapsed >= 1 && $this->target_percentage_year2 !== null) {
            return $this->target_percentage_year2;
        }

        return $this->target_percentage_year1;
    }
}
