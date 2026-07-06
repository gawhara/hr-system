<?php

namespace App\Services\Biometric;

use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\BiometricDevice;
use App\Models\Employee;
use Illuminate\Support\Carbon;

/**
 * Device punches → attendance rows.
 *
 * Raw punches are stored verbatim (deduplicated) so aggregation is always
 * re-runnable. A day's record is: first punch = check-in, last punch =
 * check-out, lateness measured against the employee's shift + grace.
 * Rows entered manually by HR are never overwritten by the machine.
 */
class AttendancePullService
{
    public function __construct(private BiometricConnector $connector)
    {
    }

    /**
     * @return array{fetched: int, new: int, unmatched: int, days: int}
     */
    public function pullDevice(BiometricDevice $device): array
    {
        try {
            $punches = $this->connector->fetchPunches($device);
        } catch (\RuntimeException $exception) {
            $device->update(['last_error' => $exception->getMessage()]);

            throw $exception;
        }

        $device->forceFill([
            'last_seen_at' => now(),
            'last_error' => null,
        ])->save();

        // Device user id → employee id, scoped to the device's company.
        $employeeMap = Employee::where('company_id', $device->company_id)
            ->whereNotNull('biometric_user_id')
            ->pluck('id', 'biometric_user_id');

        $stats = ['fetched' => count($punches), 'new' => 0, 'unmatched' => 0, 'days' => 0];
        $touched = [];

        foreach ($punches as $punch) {
            $employeeId = $employeeMap[$punch['device_user_id']] ?? null;

            if ($employeeId === null) {
                $stats['unmatched']++;
            }

            $created = AttendancePunch::firstOrCreate(
                [
                    'biometric_device_id' => $device->id,
                    'device_user_id' => $punch['device_user_id'],
                    'punched_at' => $punch['punched_at'],
                ],
                [
                    'employee_id' => $employeeId,
                    'state' => $punch['state'],
                    'punch_type' => $punch['punch_type'],
                ],
            );

            if ($created->wasRecentlyCreated) {
                $stats['new']++;

                if ($employeeId !== null) {
                    $touched[$employeeId][Carbon::parse($punch['punched_at'])->toDateString()] = true;
                }
            }
        }

        foreach ($touched as $employeeId => $dates) {
            foreach (array_keys($dates) as $date) {
                $this->aggregateDay((int) $employeeId, $date);
                $stats['days']++;
            }
        }

        $device->forceFill(['last_pulled_at' => now()])->save();

        return $stats;
    }

    public function aggregateDay(int $employeeId, string $date): void
    {
        $employee = Employee::with('shift')->find($employeeId);

        if ($employee === null) {
            return;
        }

        $times = AttendancePunch::where('employee_id', $employeeId)
            ->whereDate('punched_at', $date)
            ->orderBy('punched_at')
            ->pluck('punched_at');

        if ($times->isEmpty()) {
            return;
        }

        $existing = AttendanceRecord::where('employee_id', $employeeId)
            ->whereDate('work_date', $date)
            ->first();

        // HR-entered rows win over the machine — corrections stay corrections.
        if ($existing !== null && $existing->source !== 'biometric') {
            return;
        }

        $checkIn = $times->first();
        $checkOut = $times->count() > 1 ? $times->last() : null;

        $lateMinutes = 0;

        if ($employee->shift !== null) {
            $shiftStart = Carbon::parse($date . ' ' . $employee->shift->starts_at)
                ->addMinutes((int) $employee->shift->grace_minutes);

            $lateMinutes = max(0, $shiftStart->diffInMinutes($checkIn, false));
        }

        AttendanceRecord::updateOrCreate(
            ['employee_id' => $employeeId, 'work_date' => $date],
            [
                'check_in' => $checkIn->format('H:i:s'),
                'check_out' => $checkOut?->format('H:i:s'),
                'status' => 'present',
                'late_minutes' => (int) $lateMinutes,
                'absence_minutes' => 0,
                'source' => 'biometric',
            ],
        );
    }
}
