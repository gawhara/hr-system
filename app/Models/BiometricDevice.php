<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BiometricDevice extends Model
{
    use LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'last_pulled_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('biometric_device')
            ->logOnly(['name_ar', 'host', 'port', 'comm_key', 'company_id', 'branch_id', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function punches()
    {
        return $this->hasMany(AttendancePunch::class);
    }

    /**
     * Health indicator for the devices dashboard.
     */
    public function connectionStatus(): string
    {
        return match (true) {
            ! $this->is_active => 'disabled',
            $this->last_error !== null => 'error',
            $this->last_seen_at === null => 'never',
            $this->last_seen_at->gt(now()->subHours(1)) => 'online',
            default => 'stale',
        };
    }
}
