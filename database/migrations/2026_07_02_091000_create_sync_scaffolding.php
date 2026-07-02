<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Offline Strategy A (branch-local server + central sync) scaffolding.
     * Every offline-writable table gets a globally unique `uuid` (sync
     * identity across branch databases — local auto-increment ids never
     * leave the branch) and a `synced_at` timestamp (null = not yet pushed
     * to the central server).
     */
    private const SYNCABLE_TABLES = [
        'employees',
        'attendance_records',
        'leave_balances',
        'leave_requests',
        'payroll_cycles',
        'payroll_items',
    ];

    public function up(): void
    {
        foreach (self::SYNCABLE_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
                $table->timestamp('synced_at')->nullable();
            });
        }

        Schema::create('sync_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction', 10); // push | pull
            $table->timestamp('last_synced_at')->nullable();
            $table->unsignedInteger('pending_push_count')->default(0);
            $table->unsignedInteger('pending_pull_count')->default(0);
            $table->timestamp('last_conflict_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sync_queue', function (Blueprint $table) {
            $table->id();
            $table->string('record_type'); // model class or table name
            $table->uuid('record_uuid');
            $table->string('operation', 10); // create | update | delete
            $table->json('payload')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_name')->nullable();
            $table->string('status', 20)->default('pending'); // pending | synced | conflict
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['record_type', 'record_uuid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_queue');
        Schema::dropIfExists('sync_log');

        foreach (self::SYNCABLE_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropUnique(['uuid']);
                $table->dropColumn(['uuid', 'synced_at']);
            });
        }
    }
};
