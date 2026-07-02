<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PayrollItem extends Model
{
    use LogsActivity;
    use Syncable;

    protected $guarded = [];

    protected static function booted(): void
    {
        // Locked payroll runs are immutable at the model layer, not just the
        // UI — corrections must go through an adjustment run (AGENT.md).
        $guard = function (PayrollItem $item) {
            if ($item->cycle()->value('status') === PayrollCycle::STATUS_LOCKED) {
                throw new \DomainException('Payroll run is locked; create an adjustment run instead.');
            }
        };

        static::updating($guard);
        static::deleting($guard);
    }

    public function cycle()
    {
        return $this->belongsTo(PayrollCycle::class, 'payroll_cycle_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('payroll')
            ->logOnly([
                'basic_salary',
                'housing_allowance',
                'transportation_allowance',
                'other_allowances',
                'social_insurance_saudi',
                'gross_total',
                'total_deductions',
                'net_salary',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
