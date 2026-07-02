<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NitaqatCalculationBatch extends Model
{
    protected $fillable = [
        'economic_activity_id',
        'company_id',
        'total_employees',
        'total_saudis_headcount',
        'total_weighted_saudis',
        'achieved_percentage',
        'required_percentage',
        'band',
        'additional_saudis_needed',
        'raw_input',
        'breakdown',
        'created_by',
    ];

    protected $casts = [
        'raw_input' => 'array',
        'breakdown' => 'array',
    ];

    public function economicActivity()
    {
        return $this->belongsTo(EconomicActivity::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
