<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('economic_activities', function (Blueprint $table) {
            $table->id();
            $table->string('isic_code')->nullable();
            $table->string('name_ar');
            $table->unsignedInteger('min_establishment_size')->default(1);
            $table->decimal('target_percentage_year1', 5, 4);
            $table->decimal('target_percentage_year2', 5, 4)->nullable();
            $table->decimal('target_percentage_year3', 5, 4)->nullable();
            $table->date('plan_effective_date');
            $table->string('source_reference')->nullable();
            $table->date('verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['isic_code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('economic_activities');
    }
};
