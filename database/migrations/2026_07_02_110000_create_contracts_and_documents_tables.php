<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reference data: alert lead times are configurable per document type
        // (AGENT.md expiry-alert requirement), never hardcoded in the alert job.
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('requires_expiry')->default(true);
            $table->json('alert_days')->nullable(); // e.g. [90, 60, 30]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();
            $table->string('document_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_file_name')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('synced_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['expiry_date']);
            $table->index(['employee_id', 'document_type_id']);
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('contract_number')->nullable();
            $table->string('contract_type', 50); // fixed | open
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->date('probation_ends_on')->nullable();
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('housing_allowance', 12, 2)->default(0);
            $table->decimal('transportation_allowance', 12, 2)->default(0);
            $table->decimal('other_allowances', 12, 2)->default(0);
            $table->string('status', 20)->default('active'); // active | expired | terminated
            $table->timestamp('terminated_at')->nullable();
            $table->text('termination_reason')->nullable();
            $table->string('file_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('synced_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('document_types');
    }
};
