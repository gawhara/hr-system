<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PayrollCycle extends Model
{
    use LogsActivity;
    use Syncable;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_LOCKED = 'locked';

    /**
     * Forward-only workflow (AGENT.md): draft → under_review → approved →
     * locked, with one backward edge (review rejection returns to draft).
     * Locked is terminal — corrections go through an adjustment run.
     */
    public const TRANSITIONS = [
        self::STATUS_DRAFT => [self::STATUS_UNDER_REVIEW],
        self::STATUS_UNDER_REVIEW => [self::STATUS_APPROVED, self::STATUS_DRAFT],
        self::STATUS_APPROVED => [self::STATUS_LOCKED],
        self::STATUS_LOCKED => [],
    ];

    protected $guarded = [];

    protected $casts = [
        'period_starts_on' => 'date',
        'period_ends_on' => 'date',
        'processed_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('payroll')
            ->logOnly(['status', 'reviewed_by', 'approved_by', 'locked_by', 'parent_cycle_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function items()
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function parentCycle()
    {
        return $this->belongsTo(self::class, 'parent_cycle_id');
    }

    public function adjustmentRuns()
    {
        return $this->hasMany(self::class, 'parent_cycle_id');
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function transitionTo(string $status, User $actor): void
    {
        if (! $this->canTransitionTo($status)) {
            throw new \DomainException("Invalid payroll transition: {$this->status} → {$status}");
        }

        $stamps = match ($status) {
            self::STATUS_UNDER_REVIEW => ['reviewed_by' => $actor->id, 'reviewed_at' => now()],
            self::STATUS_APPROVED => ['approved_by' => $actor->id, 'approved_at' => now()],
            self::STATUS_LOCKED => ['locked_by' => $actor->id, 'locked_at' => now()],
            // Rejection back to draft clears the review stamp.
            self::STATUS_DRAFT => ['reviewed_by' => null, 'reviewed_at' => null],
            default => [],
        };

        $this->update(['status' => $status] + $stamps);
    }

    /**
     * Offline rule (AGENT.md): a branch node may only lock a run whose items
     * are all confirmed synced. Central/standalone nodes have no such gate.
     */
    public function hasUnsyncedData(): bool
    {
        if (config('hr.sync.role', 'standalone') !== 'branch') {
            return false;
        }

        return $this->items()->whereNull('synced_at')->exists()
            || $this->synced_at === null;
    }
}
