<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $guarded = [];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
