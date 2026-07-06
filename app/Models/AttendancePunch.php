<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendancePunch extends Model
{
    protected $guarded = [];

    protected $casts = [
        'punched_at' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(BiometricDevice::class, 'biometric_device_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
