<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use Syncable;

    protected $guarded = [];

    protected $casts = [
        // Y-m-d storage on every driver (SQLite tests store the cast's
        // serialized string), so plain where('work_date', $date) matches —
        // no whereDate() wrapper needed, MySQL keeps using the index.
        'work_date' => 'date:Y-m-d',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
