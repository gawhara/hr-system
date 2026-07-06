<?php

namespace App\Services\Biometric;

use App\Models\BiometricDevice;

/**
 * Device-protocol boundary (AGENT.md: biometric integration is an
 * interface, not a hard dependency). The ZKTeco implementation talks the
 * UDP 4370 protocol; other brands or a push-style gateway can implement
 * this same contract later without touching the pull pipeline.
 */
interface BiometricConnector
{
    /**
     * Probe the device and return its identity.
     *
     * @return array{serial_number: ?string, device_name: ?string, version: ?string}
     *
     * @throws \RuntimeException when the device is unreachable.
     */
    public function probe(BiometricDevice $device): array;

    /**
     * Fetch every attendance punch stored on the device.
     *
     * @return array<int, array{device_user_id: string, punched_at: \DateTimeInterface, state: ?int, punch_type: ?int}>
     *
     * @throws \RuntimeException when the device is unreachable.
     */
    public function fetchPunches(BiometricDevice $device): array;
}
