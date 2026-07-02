<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentExpiryAlert extends Model
{
    protected $guarded = [];

    protected $casts = [
        'notified_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(EmployeeDocument::class, 'employee_document_id');
    }
}
