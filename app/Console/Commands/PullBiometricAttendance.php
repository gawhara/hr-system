<?php

namespace App\Console\Commands;

use App\Models\BiometricDevice;
use App\Services\Biometric\AttendancePullService;
use Illuminate\Console\Command;

class PullBiometricAttendance extends Command
{
    protected $signature = 'hr:pull-attendance {--device= : Pull a single device by id}';

    protected $description = 'Pull attendance punches from active biometric devices and build attendance records';

    public function handle(AttendancePullService $service): int
    {
        $devices = BiometricDevice::where('is_active', true)
            ->when($this->option('device'), fn ($query, $id) => $query->whereKey($id))
            ->get();

        if ($devices->isEmpty()) {
            $this->info('No active biometric devices.');

            return self::SUCCESS;
        }

        $failures = 0;

        foreach ($devices as $device) {
            try {
                $stats = $service->pullDevice($device);

                $this->info(sprintf(
                    '%s (%s:%d): fetched %d, new %d, unmatched %d, days rebuilt %d',
                    $device->name_ar,
                    $device->host,
                    $device->port,
                    $stats['fetched'],
                    $stats['new'],
                    $stats['unmatched'],
                    $stats['days'],
                ));
            } catch (\RuntimeException $exception) {
                $failures++;
                $this->error("{$device->name_ar} ({$device->host}:{$device->port}): {$exception->getMessage()}");
            }
        }

        // Partial failure is normal fleet behavior (a branch may be offline);
        // report it without failing the whole scheduled run.
        return $failures === $devices->count() ? self::FAILURE : self::SUCCESS;
    }
}
