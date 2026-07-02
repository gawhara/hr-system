<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Contract extends Model
{
    use LogsActivity;
    use SoftDeletes;
    use Syncable;

    protected $guarded = [];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'probation_ends_on' => 'date',
        'terminated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Contracts feed the employee's profile-completion score.
        $recompute = fn (Contract $contract) => $contract->employee?->recomputeProfileCompletionQuietly();

        static::created($recompute);
        static::updated($recompute);
        static::deleted($recompute);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('contract')
            ->logOnly([
                'contract_type',
                'starts_on',
                'ends_on',
                'basic_salary',
                'housing_allowance',
                'transportation_allowance',
                'other_allowances',
                'status',
                'terminated_at',
                'termination_reason',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
