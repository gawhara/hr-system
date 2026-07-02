<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;

class EmployeeStatusHistory extends Model
{
    use Syncable;

    protected $guarded = [];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
