<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PRD (Employee Module, Bayzat reference) batch 1, adapted to the
     * existing flat-employees architecture — see AGENT.md Decision Log:
     *  - missing profile fields (marital status, address, emergency contact,
     *    direct manager, work location, probation end, avatar)
     *  - one unified 7-value employee status (replaces the parallel
     *    status + employment_status pair)
     *  - employee_status_histories (syncable, reason + actor recorded)
     *  - contract type vocabulary aligned: open → indefinite,
     *    plus training / temporary
     *  - stored profile_completion for SQL filtering/stat cards
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('marital_status', 20)->nullable()->after('gender');
            $table->text('address')->nullable()->after('phone_2');
            $table->string('emergency_contact_name')->nullable()->after('address');
            $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_contact_name');
            $table->foreignId('manager_id')->nullable()->after('user_id')->constrained('employees')->nullOnDelete();
            $table->string('work_location')->nullable()->after('shift_id');
            $table->date('probation_end_date')->nullable()->after('contract_start_date');
            $table->string('avatar_path')->nullable()->after('name_en');
            $table->unsignedTinyInteger('profile_completion')->default(0)->after('status');
        });

        // Merge employment_status into the unified status column. Rows keep
        // 'active'/'inactive' unless a stronger employment state was set.
        foreach (['terminated', 'suspended', 'on_leave'] as $employmentStatus) {
            DB::table('employees')
                ->where('employment_status', $employmentStatus)
                ->update(['status' => $employmentStatus]);
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('employment_status');
        });

        DB::table('employees')->where('contract_type', 'open')->update(['contract_type' => 'indefinite']);
        DB::table('contracts')->where('contract_type', 'open')->update(['contract_type' => 'indefinite']);

        Schema::create('employee_status_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30);
            $table->string('to_status', 30);
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_status_histories');

        DB::table('employees')->where('contract_type', 'indefinite')->update(['contract_type' => 'open']);
        DB::table('contracts')->where('contract_type', 'indefinite')->update(['contract_type' => 'open']);

        Schema::table('employees', function (Blueprint $table) {
            $table->string('employment_status', 30)->nullable();
        });

        foreach (['terminated', 'suspended', 'on_leave'] as $employmentStatus) {
            DB::table('employees')
                ->where('status', $employmentStatus)
                ->update(['employment_status' => $employmentStatus, 'status' => 'active']);
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_id');
            $table->dropColumn([
                'marital_status', 'address', 'emergency_contact_name',
                'emergency_contact_phone', 'work_location', 'probation_end_date',
                'avatar_path', 'profile_completion',
            ]);
        });
    }
};
