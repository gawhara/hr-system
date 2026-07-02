<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('full_name_arabic')->nullable()->after('passport_full_name_english');
            $table->string('full_name_english')->nullable()->after('full_name_arabic');
            $table->string('job_title')->nullable()->after('work_location');
            $table->date('start_date')->nullable()->after('contract_type');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('bank', 100)->nullable()->after('bank_name');
            $table->string('branch_text', 100)->nullable()->after('branch_id');

            $table->decimal('overtime', 12, 2)->default(0)->after('basic_salary');
            $table->decimal('training_labor_wages', 12, 2)->default(0)->after('other_allowances');
            $table->decimal('previous_dues', 12, 2)->default(0)->after('training_labor_wages');
            $table->decimal('total', 12, 2)->default(0)->after('previous_dues');
            $table->decimal('basic_salary_gosi', 12, 2)->default(0)->after('total');
            $table->decimal('housing_allowance_gosi', 12, 2)->default(0)->after('basic_salary_gosi');
            $table->decimal('other_gosi_items', 12, 2)->default(0)->after('housing_allowance_gosi');
            $table->decimal('diff_registered_housing_allowance', 12, 2)->default(0)->after('other_gosi_items');
            $table->decimal('absence_deduction', 12, 2)->default(0)->after('diff_registered_housing_allowance');
            $table->decimal('delay_deduction', 12, 2)->default(0)->after('absence_deduction');
            $table->decimal('leave_deduction', 12, 2)->default(0)->after('delay_deduction');
            $table->decimal('warnings_penalties', 12, 2)->default(0)->after('leave_deduction');
            $table->decimal('insurance_deduction', 12, 2)->default(0)->after('warnings_penalties');
            $table->decimal('loans', 12, 2)->default(0)->after('insurance_deduction');
            $table->decimal('social_insurance_saudi', 12, 2)->default(0)->after('loans');
            $table->decimal('total_deductions', 12, 2)->default(0)->after('social_insurance_saudi');
            $table->decimal('cash', 12, 2)->default(0)->after('total_deductions');
            $table->decimal('al_rajhi_transfer', 12, 2)->default(0)->after('cash');
            $table->decimal('bank_albilad_transfer', 12, 2)->default(0)->after('al_rajhi_transfer');
            $table->decimal('riyad_bank_transfer', 12, 2)->default(0)->after('bank_albilad_transfer');
            $table->decimal('remaining_salary', 12, 2)->default(0)->after('riyad_bank_transfer');
            $table->string('employment_status', 30)->nullable()->after('profile_completion');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'full_name_arabic',
                'full_name_english',
                'job_title',
                'start_date',
                'end_date',
                'bank',
                'branch_text',
                'overtime',
                'training_labor_wages',
                'previous_dues',
                'total',
                'basic_salary_gosi',
                'housing_allowance_gosi',
                'other_gosi_items',
                'diff_registered_housing_allowance',
                'absence_deduction',
                'delay_deduction',
                'leave_deduction',
                'warnings_penalties',
                'insurance_deduction',
                'loans',
                'social_insurance_saudi',
                'total_deductions',
                'cash',
                'al_rajhi_transfer',
                'bank_albilad_transfer',
                'riyad_bank_transfer',
                'remaining_salary',
                'employment_status',
            ]);
        });
    }
};
