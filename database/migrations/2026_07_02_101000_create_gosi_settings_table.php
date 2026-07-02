<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * GOSI contribution rates are regulator-set figures and live here as
     * per-company configuration (establishment numbers and registration
     * dates differ per legal entity), never as code constants — AGENT.md
     * guiding principle #1. `verified_at` records when an operator last
     * confirmed the values against official GOSI/HRSD sources.
     */
    public function up(): void
    {
        Schema::create('gosi_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('key');
            // Wide enough for both rates (0.0975) and wage caps (45000.00).
            $table->decimal('value', 12, 4);
            $table->string('label_ar');
            $table->string('label_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            // company_id null = group-wide default; a company row overrides it.
            $table->unique(['company_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gosi_settings');
    }
};
