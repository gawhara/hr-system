<?php

namespace App\Services\Biometric;

use App\Models\BiometricDevice;
use Carbon\Carbon;
use CodingLibs\ZktecoPhp\Libs\ZKTeco;

/**
 * ZKTeco standalone-SDK protocol over UDP port 4370. Works against any
 * address the server can route to: branch LAN, site-to-site VPN, static
 * IP, or a DDNS hostname (the router must forward UDP 4370 to the device).
 */
class ZktecoConnector implements BiometricConnector
{
    private const TIMEOUT_SECONDS = 8;

    public function probe(BiometricDevice $device): array
    {
        $zk = $this->open($device);

        try {
            return [
                'serial_number' => $this->stringOrNull($zk->serialNumber()),
                'device_name' => $this->stringOrNull($zk->deviceName()),
                'version' => $this->stringOrNull($zk->version()),
            ];
        } finally {
            $this->close($zk);
        }
    }

    public function fetchPunches(BiometricDevice $device): array
    {
        $zk = $this->open($device);

        try {
            // Pause live matching while reading the log, per ZK SDK guidance.
            $zk->disableDevice();

            $punches = [];

            foreach ($zk->getAttendances() as $row) {
                $userId = trim((string) ($row['user_id'] ?? $row['id'] ?? ''));
                $timestamp = $row['timestamp'] ?? null;

                if ($userId === '' || ! $timestamp) {
                    continue;
                }

                $punches[] = [
                    'device_user_id' => $userId,
                    'punched_at' => Carbon::parse($timestamp),
                    'state' => isset($row['state']) ? (int) $row['state'] : null,
                    'punch_type' => isset($row['type']) ? (int) $row['type'] : null,
                ];
            }

            return $punches;
        } finally {
            try {
                $zk->enableDevice();
            } catch (\Throwable) {
                // Best effort — never mask the punch data on re-enable failure.
            }

            $this->close($zk);
        }
    }

    private function open(BiometricDevice $device): ZKTeco
    {
        $zk = new ZKTeco(
            $device->host,
            (int) $device->port,
            shouldPing: false,
            timeout: self::TIMEOUT_SECONDS,
            password: (int) $device->comm_key,
        );

        $connected = false;

        try {
            $connected = $zk->connect();
        } catch (\Throwable $exception) {
            throw new \RuntimeException(
                "تعذر الاتصال بالجهاز {$device->host}:{$device->port} — " . $exception->getMessage(),
                previous: $exception,
            );
        }

        if (! $connected) {
            throw new \RuntimeException(
                "تعذر الاتصال بالجهاز {$device->host}:{$device->port} — تحقق من الشبكة ومفتاح الاتصال (Comm Key)."
            );
        }

        return $zk;
    }

    private function close(ZKTeco $zk): void
    {
        try {
            $zk->disconnect();
        } catch (\Throwable) {
            // Socket already gone — nothing to clean up.
        }
    }

    private function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
