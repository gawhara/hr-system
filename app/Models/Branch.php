<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }
}
