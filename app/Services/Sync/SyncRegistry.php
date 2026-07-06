<?php

namespace App\Services\Sync;

use App\Models\AttendanceRecord;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\PayrollCycle;
use App\Models\PayrollItem;
use Illuminate\Database\Eloquent\Model;

/**
 * Strategy A sync contract shared by branch and central nodes.
 *
 * Records travel as raw attribute payloads keyed by uuid. Local
 * auto-increment ids and per-node user FKs never leave a node; FKs to other
 * synced records are translated to `<relation>_uuid` on the wire. FKs to
 * reference data (companies, branches, leave types, document types, …) pass
 * through as-is — reference tables are seeded identically on every node and
 * managed centrally by design.
 *
 * Encrypted columns travel as ciphertext, which requires every node to share
 * the same APP_KEY (documented in the README).
 */
class SyncRegistry
{
    /**
     * Ordered so that parents apply before children on the receiving side.
     *
     * @var array<string, class-string<Model>>
     */
    public const MODELS = [
        'employee' => Employee::class,
        'attendance_record' => AttendanceRecord::class,
        'leave_balance' => LeaveBalance::class,
        'leave_request' => LeaveRequest::class,
        'payroll_cycle' => PayrollCycle::class,
        'payroll_item' => PayrollItem::class,
        'employee_document' => EmployeeDocument::class,
        'contract' => Contract::class,
    ];

    /**
     * Local FK column => [wire key, type-key of the model the uuid points to].
     *
     * @var array<string, array<string, array{0: string, 1: string}>>
     */
    public const UUID_FOREIGN_KEYS = [
        'attendance_record' => ['employee_id' => ['employee_uuid', 'employee']],
        'leave_balance' => ['employee_id' => ['employee_uuid', 'employee']],
        'leave_request' => ['employee_id' => ['employee_uuid', 'employee']],
        'payroll_cycle' => ['parent_cycle_id' => ['parent_cycle_uuid', 'payroll_cycle']],
        'payroll_item' => [
            'employee_id' => ['employee_uuid', 'employee'],
            'payroll_cycle_id' => ['payroll_cycle_uuid', 'payroll_cycle'],
        ],
        'employee_document' => ['employee_id' => ['employee_uuid', 'employee']],
        'contract' => ['employee_id' => ['employee_uuid', 'employee']],
    ];

    /**
     * Per-node user FKs: meaningless on another node, stripped from the wire.
     *
     * @var array<int, string>
     */
    public const LOCAL_ONLY_COLUMNS = [
        'id', 'user_id', 'created_by', 'updated_by', 'approved_by',
        'processed_by', 'reviewed_by', 'locked_by', 'synced_at',
    ];

    /**
     * Conflict-sensitive fields (AGENT.md): when both nodes changed a record
     * and any of these differ, the incoming record is quarantined for manual
     * HR review instead of auto-resolved.
     *
     * @var array<string, array<int, string>>
     */
    public const CONFLICT_SENSITIVE = [
        'employee' => [
            'basic_salary', 'housing_allowance', 'transportation_allowance',
            'other_allowances', 'gosi_basic_salary', 'gosi_housing_allowance',
            'saudi_non_saudi', 'nationality', 'national_id', 'iban', 'status',
        ],
        'payroll_cycle' => ['status'],
        'payroll_item' => ['gross_total', 'total_deductions', 'net_salary'],
        'contract' => ['basic_salary', 'status', 'terminated_at'],
    ];

    /** @var array<string, array<int, string>> */
    private static array $writableColumnsCache = [];

    /**
     * S3 hardening: the applier only ever writes real table columns, minus
     * local ids/user FKs — client payloads can't smuggle arbitrary keys.
     */
    public static function writableColumns(string $type): array
    {
        if (! isset(self::$writableColumnsCache[$type])) {
            $modelClass = self::modelFor($type);
            $table = (new $modelClass)->getTable();

            self::$writableColumnsCache[$type] = array_values(array_diff(
                \Illuminate\Support\Facades\Schema::getColumnListing($table),
                self::LOCAL_ONLY_COLUMNS,
                ['uuid', 'synced_at'],
            ));
        }

        return self::$writableColumnsCache[$type];
    }

    public static function typeFor(Model $model): string
    {
        $type = array_search($model::class, self::MODELS, true);

        if ($type === false) {
            throw new \InvalidArgumentException('Model is not syncable: ' . $model::class);
        }

        return $type;
    }

    public static function modelFor(string $type): string
    {
        return self::MODELS[$type]
            ?? throw new \InvalidArgumentException("Unknown sync type: {$type}");
    }

    /**
     * Raw DB attributes → wire payload (uuid-keyed, FKs translated).
     */
    public static function serialize(Model $model): array
    {
        $type = self::typeFor($model);
        $attributes = $model->getRawOriginal();

        foreach (self::LOCAL_ONLY_COLUMNS as $column) {
            unset($attributes[$column]);
        }

        foreach (self::UUID_FOREIGN_KEYS[$type] ?? [] as $column => [$wireKey, $targetType]) {
            $localId = $attributes[$column] ?? null;
            unset($attributes[$column]);

            $attributes[$wireKey] = $localId !== null
                ? self::modelFor($targetType)::whereKey($localId)->value('uuid')
                : null;
        }

        return [
            'type' => $type,
            'uuid' => $model->uuid,
            'updated_at' => $model->updated_at?->toIso8601String(),
            'attributes' => $attributes,
        ];
    }
}
