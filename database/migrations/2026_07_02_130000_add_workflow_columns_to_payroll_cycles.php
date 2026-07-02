<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payroll run workflow per AGENT.md: draft → under_review → approved →
     * locked. Locked runs are immutable; corrections happen in a new
     * adjustment run pointing back via parent_cycle_id.
     */
    public function up(): void
    {
        Schema::table('payroll_cycles', function (Blueprint $table) {
            $table->foreignId('reviewed_by')->nullable()->after('processed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->foreignId('approved_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreignId('locked_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable()->after('locked_by');
            $table->foreignId('parent_cycle_id')->nullable()->after('locked_at')->constrained('payroll_cycles')->nullOnDelete();
            // 0 = the month's regular run; adjustments count up from 1, so the
            // one-regular-run-per-period guarantee stays DB-enforced.
            $table->unsignedTinyInteger('run_sequence')->default(0)->after('parent_cycle_id');
        });

        // New index first, drop second: the company_id FK needs a supporting
        // index at all times, and MySQL refuses to drop the old unique while
        // it's the only one.
        Schema::table('payroll_cycles', function (Blueprint $table) {
            $table->unique(['company_id', 'branch_id', 'year', 'month', 'run_sequence'], 'payroll_period_run_unique');
        });

        Schema::table('payroll_cycles', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'branch_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::table('payroll_cycles', function (Blueprint $table) {
            $table->unique(['company_id', 'branch_id', 'year', 'month']);
        });

        Schema::table('payroll_cycles', function (Blueprint $table) {
            $table->dropUnique('payroll_period_run_unique');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('locked_by');
            $table->dropConstrainedForeignId('parent_cycle_id');
            $table->dropColumn(['reviewed_at', 'approved_at', 'locked_at', 'run_sequence']);
        });
    }
};
