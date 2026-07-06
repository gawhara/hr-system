<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Biometric attendance (AGENT.md Phase 5 item 4, ZKTeco fleet).
     *
     * biometric_devices — the central registry for all four companies:
     * a device is reachable by hostname/IP (LAN, VPN, static IP, or DDNS
     * name) + UDP port 4370. Device config is node-local reference data
     * and is NOT synced; the attendance_records it produces already sync.
     *
     * attendance_punches — raw punch events exactly as pulled from the
     * device, deduplicated by (device, device user, time). Kept as local
     * evidence so aggregation can always be re-run; only the aggregated
     * attendance_records travel to the central server.
     */
    public function up(): void
    {
        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('brand', 30)->default('zkteco');
            $table->string('model', 100)->nullable();
            $table->string('host'); // IP or DDNS hostname
            $table->unsignedInteger('port')->default(4370);
            $table->unsignedInteger('comm_key')->default(0);
            $table->string('serial_number', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_pulled_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['host', 'port']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('attendance_punches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biometric_device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_user_id', 50);
            $table->dateTime('punched_at');
            $table->unsignedSmallInteger('state')->nullable();
            $table->unsignedSmallInteger('punch_type')->nullable();
            $table->timestamps();

            $table->unique(['biometric_device_id', 'device_user_id', 'punched_at'], 'punch_unique');
            $table->index(['employee_id', 'punched_at']);
        });

        Schema::table('employees', function (Blueprint $table) {
            // Enrollment id on the fingerprint device (ZK "user id").
            $table->string('biometric_user_id', 50)->nullable()->after('hr_employee_id')->index();
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            // manual = HR-entered; biometric = built from device punches.
            // The puller never overwrites manual rows.
            $table->string('source', 20)->default('manual');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['biometric_user_id']);
            $table->dropColumn('biometric_user_id');
        });

        Schema::dropIfExists('attendance_punches');
        Schema::dropIfExists('biometric_devices');
    }
};
