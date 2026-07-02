<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use Syncable;

    protected $guarded = [];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
