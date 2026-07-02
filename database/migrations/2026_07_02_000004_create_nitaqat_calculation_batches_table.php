<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nitaqat_calculation_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('economic_activity_id')->constrained('economic_activities')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('total_employees');
            $table->unsignedInteger('total_saudis_headcount');
            $table->decimal('total_weighted_saudis', 10, 4);
            $table->decimal('achieved_percentage', 7, 4);
            $table->decimal('required_percentage', 7, 4);
            $table->string('band');
            $table->unsignedInteger('additional_saudis_needed')->default(0);
            $table->json('raw_input')->nullable();
            $table->json('breakdown')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nitaqat_calculation_batches');
    }
};
