<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use Syncable;

    protected $guarded = [];

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}
