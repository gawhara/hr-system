<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('cr_number')->nullable()->unique();
            $table->string('gosi_number')->nullable()->unique();
            $table->string('logo_path')->nullable();
            $table->text('address_ar')->nullable();
            $table->text('address_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('city_ar');
            $table->string('city_en');
            $table->text('address_ar')->nullable();
            $table->text('address_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'is_active']);
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar');
            $table->string('title_en');
            $table->string('job_grade')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedSmallInteger('grace_minutes')->default(15);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('employee')->after('password');
            $table->foreignId('current_company_id')->nullable()->after('role')->constrained('companies')->nullOnDelete();
        });

        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_code', 50)->nullable()->unique();
            $table->string('financial_employee_id', 50)->nullable()->unique();
            $table->string('hr_employee_id', 50)->nullable()->unique();
            $table->string('national_id', 10)->nullable()->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('iqama_full_name_arabic')->nullable();
            $table->string('iqama_full_name_english')->nullable();
            $table->string('passport_full_name_arabic')->nullable();
            $table->string('passport_full_name_english')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone', 10)->nullable()->unique();
            $table->string('phone_2', 10)->nullable()->unique();
            $table->string('nationality', 100)->nullable();
            $table->string('saudi_non_saudi', 20)->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->date('iqama_expiry')->nullable();
            $table->string('passport_id', 50)->nullable();
            $table->date('passport_expiry')->nullable();
            $table->string('contract_type', 50)->nullable();
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('iban', 34)->nullable();
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('housing_allowance', 12, 2)->default(0);
            $table->decimal('transportation_allowance', 12, 2)->default(0);
            $table->decimal('other_allowances', 12, 2)->default(0);
            $table->decimal('gosi_basic_salary', 12, 2)->default(0);
            $table->decimal('gosi_housing_allowance', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->string('employment_status', 30)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'department_id']);
            $table->index(['company_id', 'status']);
            $table->index(['iqama_expiry', 'passport_expiry', 'contract_end_date']);
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('status')->default('present');
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('absence_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'work_date']);
            $table->index(['work_date', 'status']);
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->unsignedSmallInteger('default_days')->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->timestamps();
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('entitled_days', 6, 2)->default(0);
            $table->decimal('used_days', 6, 2)->default(0);
            $table->decimal('carried_days', 6, 2)->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'year']);
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->decimal('days', 6, 2);
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['starts_on', 'ends_on']);
        });

        Schema::create('payroll_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('status')->default('draft');
            $table->date('period_starts_on');
            $table->date('period_ends_on');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'year', 'month']);
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('housing_allowance', 12, 2)->default(0);
            $table->decimal('transportation_allowance', 12, 2)->default(0);
            $table->decimal('other_allowances', 12, 2)->default(0);
            $table->decimal('overtime', 12, 2)->default(0);
            $table->decimal('previous_dues', 12, 2)->default(0);
            $table->decimal('absence_deduction', 12, 2)->default(0);
            $table->decimal('delay_deduction', 12, 2)->default(0);
            $table->decimal('leave_deduction', 12, 2)->default(0);
            $table->decimal('warnings_penalties', 12, 2)->default(0);
            $table->decimal('insurance_deduction', 12, 2)->default(0);
            $table->decimal('loans', 12, 2)->default(0);
            $table->decimal('social_insurance_saudi', 12, 2)->default(0);
            $table->decimal('cash', 12, 2)->default(0);
            $table->decimal('al_rajhi_transfer', 12, 2)->default(0);
            $table->decimal('bank_albilad_transfer', 12, 2)->default(0);
            $table->decimal('riyad_bank_transfer', 12, 2)->default(0);
            $table->decimal('gross_total', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['payroll_cycle_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_cycles');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('company_user');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_company_id');
            $table->dropColumn('role');
        });
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');
    }
};
