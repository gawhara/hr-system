<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class Employee extends Model
{
    use LogsActivity;
    use SoftDeletes;
    use Syncable;

    /**
     * Encrypted at rest; audit entries only ever carry masked forms of these.
     */
    private const ENCRYPTED_PII = ['national_id', 'iban', 'passport_id'];

    /**
     * Unified status vocabulary (PRD §12).
     */
    public const STATUSES = [
        'active', 'inactive', 'probation', 'on_leave',
        'suspended', 'resigned', 'terminated',
    ];

    /**
     * Statuses still counted as employed for headcount/Nitaqat purposes.
     * PLACEHOLDER judgment — verify against official GOSI/Nitaqat counting
     * rules (e.g. whether suspended employees count) before production use.
     */
    public const EMPLOYED_STATUSES = ['active', 'probation', 'on_leave', 'suspended'];

    public const CONTRACT_TYPES = ['fixed', 'indefinite', 'training', 'temporary'];

    public const STATUS_LABELS_AR = [
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'probation' => 'تحت التجربة',
        'on_leave' => 'في إجازة',
        'suspended' => 'موقوف',
        'resigned' => 'مستقيل',
        'terminated' => 'منتهي الخدمة',
    ];

    public const CONTRACT_TYPE_LABELS_AR = [
        'fixed' => 'محدد المدة',
        'indefinite' => 'غير محدد المدة',
        'training' => 'تدريب',
        'temporary' => 'مؤقت',
    ];

    protected $guarded = [];

    protected $casts = [
        'birth_date' => 'date',
        'iqama_expiry' => 'date',
        'passport_expiry' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'probation_end_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'national_id' => 'encrypted',
        'iban' => 'encrypted',
        'passport_id' => 'encrypted',
        'overtime' => 'decimal:2',
        'training_labor_wages' => 'decimal:2',
        'previous_dues' => 'decimal:2',
        'total' => 'decimal:2',
        'basic_salary_gosi' => 'decimal:2',
        'housing_allowance_gosi' => 'decimal:2',
        'other_gosi_items' => 'decimal:2',
        'diff_registered_housing_allowance' => 'decimal:2',
        'absence_deduction' => 'decimal:2',
        'delay_deduction' => 'decimal:2',
        'leave_deduction' => 'decimal:2',
        'warnings_penalties' => 'decimal:2',
        'insurance_deduction' => 'decimal:2',
        'loans' => 'decimal:2',
        'social_insurance_saudi' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'cash' => 'decimal:2',
        'al_rajhi_transfer' => 'decimal:2',
        'bank_albilad_transfer' => 'decimal:2',
        'riyad_bank_transfer' => 'decimal:2',
        'remaining_salary' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Ciphertext is randomized, so uniqueness/exact search use the hash.
        static::saving(function (Employee $employee) {
            if ($employee->isDirty('national_id')) {
                $employee->national_id_hash = $employee->national_id !== null
                    ? hash('sha256', $employee->national_id)
                    : null;
            }

            $employee->profile_completion = $employee->profileCompletion()['percent'];
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('employee')
            ->logOnly([
                'basic_salary',
                'housing_allowance',
                'transportation_allowance',
                'other_allowances',
                'full_name_arabic',
                'full_name_english',
                'iqama_full_name_arabic',
                'iqama_full_name_english',
                'passport_full_name_arabic',
                'passport_full_name_english',
                'job_title',
                'branch_text',
                'start_date',
                'end_date',
                'overtime',
                'training_labor_wages',
                'previous_dues',
                'total',
                'gosi_basic_salary',
                'gosi_housing_allowance',
                'basic_salary_gosi',
                'housing_allowance_gosi',
                'other_gosi_items',
                'diff_registered_housing_allowance',
                'absence_deduction',
                'delay_deduction',
                'leave_deduction',
                'warnings_penalties',
                'insurance_deduction',
                'loans',
                'social_insurance_saudi',
                'total_deductions',
                'cash',
                'al_rajhi_transfer',
                'bank_albilad_transfer',
                'riyad_bank_transfer',
                'remaining_salary',
                'nationality',
                'saudi_non_saudi',
                'status',
                'employment_status',
                'manager_id',
                'probation_end_date',
                'bank_name',
                'bank',
                'iban',
                'national_id',
                'passport_id',
                'iqama_expiry',
                'passport_expiry',
                'contract_type',
                'contract_start_date',
                'contract_end_date',
                'branch_id',
                'department_id',
                'position_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity): void
    {
        $properties = $activity->properties->toArray();

        foreach (['attributes', 'old'] as $bag) {
            foreach (self::ENCRYPTED_PII as $field) {
                if (isset($properties[$bag][$field]) && is_string($properties[$bag][$field])) {
                    $properties[$bag][$field] = self::maskPii($properties[$bag][$field]);
                }
            }
        }

        $activity->properties = collect($properties);
    }

    private static function maskPii(string $value): string
    {
        if (mb_strlen($value) <= 4) {
            return str_repeat('*', mb_strlen($value));
        }

        return mb_substr($value, 0, 2)
            . str_repeat('*', mb_strlen($value) - 4)
            . mb_substr($value, -2);
    }

    public function scopeWhereNationalId($query, string $nationalId)
    {
        return $query->where('national_id_hash', hash('sha256', $nationalId));
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function manager()
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function subordinates()
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(EmployeeStatusHistory::class)->latest('id');
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function payrollItems()
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function getTotalSalaryAttribute(): float
    {
        return (float) $this->basic_salary
            + (float) $this->housing_allowance
            + (float) $this->transportation_allowance
            + (float) $this->other_allowances;
    }

    /**
     * Weighted profile completion (PRD §8): section weights
     * basic 20% / personal 20% / work 20% / contract 15% / salary 15% /
     * required documents 10%. Returns the rounded percent plus the list of
     * missing items (Arabic labels) so HR can see exactly what to complete.
     */
    public function profileCompletion(): array
    {
        $basic = [
            'الرقم المالي للموظف' => $this->financial_employee_id ?: $this->employee_code,
            'الاسم بالعربية' => $this->name_ar,
            'الاسم بالإنجليزية' => $this->name_en,
            'البريد الإلكتروني' => $this->email,
            'رقم الجوال' => $this->phone,
        ];

        $personal = [
            'الجنسية' => $this->nationality,
            'التصنيف (سعودي/غير سعودي)' => $this->saudi_non_saudi,
            'الجنس' => $this->gender,
            'تاريخ الميلاد' => $this->birth_date,
            'رقم الهوية / الإقامة' => $this->national_id,
            'الحالة الاجتماعية' => $this->marital_status,
            'جهة اتصال الطوارئ' => $this->emergency_contact_phone,
        ];

        if ($this->saudi_non_saudi === 'non_saudi') {
            $personal['تاريخ انتهاء الإقامة'] = $this->iqama_expiry;
        }

        $work = [
            'الفرع' => $this->branch_id,
            'القسم' => $this->department_id,
            'المسمى الوظيفي' => $this->position_id,
            'تاريخ الالتحاق' => $this->contract_start_date,
        ];

        $contract = [
            'نوع العقد' => $this->contract_type,
            'عقد عمل مسجل' => $this->contracts->firstWhere('status', 'active'),
        ];

        $salary = [
            'الراتب الأساسي' => (float) $this->basic_salary > 0 ? $this->basic_salary : null,
            'البنك' => $this->bank_name,
            'الآيبان' => $this->iban,
        ];

        $requiredDocuments = [
            'وثيقة عقد العمل' => $this->documents->first(fn ($doc) => $doc->type?->key === 'contract'),
        ];

        if ($this->saudi_non_saudi === 'non_saudi') {
            $requiredDocuments['صورة الإقامة'] = $this->documents->first(fn ($doc) => $doc->type?->key === 'iqama');
        }

        $weighted = [
            [0.20, $basic],
            [0.20, $personal],
            [0.20, $work],
            [0.15, $contract],
            [0.15, $salary],
            [0.10, $requiredDocuments],
        ];

        $percent = 0.0;
        $missing = [];

        foreach ($weighted as [$weight, $fields]) {
            $filled = 0;

            foreach ($fields as $label => $value) {
                if ($value !== null && $value !== '') {
                    $filled++;
                } else {
                    $missing[] = $label;
                }
            }

            $percent += $weight * ($filled / max(count($fields), 1));
        }

        return [
            'percent' => (int) round($percent * 100),
            'missing' => $missing,
        ];
    }

    public function getProfileCompletionPercentAttribute(): int
    {
        return $this->profile_completion ?? $this->profileCompletion()['percent'];
    }

    /**
     * Called when owned records (contracts, documents) change: they feed the
     * completion score but don't touch the employee row themselves. Quiet
     * update — derived data must not re-dirty the sync state or audit log.
     */
    public function recomputeProfileCompletionQuietly(): void
    {
        $this->load(['contracts', 'documents.type']);
        $this->updateQuietly(['profile_completion' => $this->profileCompletion()['percent']]);
    }

    /**
     * PRD §8 threshold band for the given (or own) completion percent.
     */
    public static function completionBand(int $percent): string
    {
        return match (true) {
            $percent < 50 => 'severe',
            $percent < 75 => 'needs_completion',
            $percent < 90 => 'good',
            default => 'complete',
        };
    }

    public function scopeEmployed($query)
    {
        return $query->whereIn('status', self::EMPLOYED_STATUSES);
    }

    /**
     * Status transitions always leave a history row (who, why, when) —
     * disable/terminate is a recorded workflow, not a field edit.
     */
    public function changeStatus(string $toStatus, ?string $reason, User $actor): void
    {
        if (! in_array($toStatus, self::STATUSES, true) || $toStatus === $this->status) {
            return;
        }

        $this->statusHistories()->create([
            'from_status' => $this->status,
            'to_status' => $toStatus,
            'reason' => $reason,
            'changed_by' => $actor->id,
        ]);

        $this->update(['status' => $toStatus, 'updated_by' => $actor->id]);
    }

    /**
     * Recent audit-log entries for this employee and everything owned by
     * them (contracts, documents) merged into a single timeline, newest
     * first. Powers the profile's "Activity" tab.
     */
    public function activityTimeline(int $limit = 20)
    {
        $contractIds = $this->contracts()->pluck('id');
        $documentIds = $this->documents()->pluck('id');

        return Activity::query()
            ->where(fn ($query) => $query
                ->where(fn ($q) => $q->where('subject_type', self::class)->where('subject_id', $this->id))
                ->orWhere(fn ($q) => $q->where('subject_type', Contract::class)->whereIn('subject_id', $contractIds))
                ->orWhere(fn ($q) => $q->where('subject_type', EmployeeDocument::class)->whereIn('subject_id', $documentIds)))
            ->with('causer')
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
