<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $guarded = [];

    protected $casts = [
        'requires_expiry' => 'boolean',
        'is_active' => 'boolean',
        'alert_days' => 'array',
    ];

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }
}
