<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmployeeDocument extends Model
{
    use LogsActivity;
    use SoftDeletes;
    use Syncable;

    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    protected static function booted(): void
    {
        // Required documents feed the employee's profile-completion score.
        $recompute = fn (EmployeeDocument $document) => $document->employee?->recomputeProfileCompletionQuietly();

        static::created($recompute);
        static::deleted($recompute);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('document')
            ->logOnly(['document_type_id', 'document_number', 'issue_date', 'expiry_date', 'file_path', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function type()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function expiryAlerts()
    {
        return $this->hasMany(DocumentExpiryAlert::class);
    }

    public function daysLeft(): ?int
    {
        return $this->expiry_date !== null
            ? (int) now()->startOfDay()->diffInDays($this->expiry_date->copy()->startOfDay(), false)
            : null;
    }

    public function expiryStatus(): string
    {
        $daysLeft = $this->daysLeft();

        return match (true) {
            $daysLeft === null => 'none',
            $daysLeft < 0 => 'expired',
            $daysLeft <= 15 => 'urgent',
            $daysLeft <= 45 => 'soon',
            default => 'healthy',
        };
    }
}
