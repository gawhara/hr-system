<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use Syncable;

    protected $guarded = [];

    protected $casts = [
        'work_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
