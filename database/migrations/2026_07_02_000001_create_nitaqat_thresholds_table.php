<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nitaqat_thresholds', function (Blueprint $table) {
            $table->id();
            $table->string('isic4_code')->nullable();
            $table->string('activity_name_ar');
            $table->string('activity_name_en')->nullable();
            $table->enum('size_category', ['small', 'medium', 'large', 'giant'])->default('medium');
            $table->unsignedInteger('min_employees')->nullable();
            $table->unsignedInteger('max_employees')->nullable();
            $table->decimal('red_max', 5, 2);
            $table->decimal('yellow_min', 5, 2);
            $table->decimal('green_low_min', 5, 2);
            $table->decimal('green_mid_min', 5, 2);
            $table->decimal('green_high_min', 5, 2);
            $table->decimal('platinum_min', 5, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('source_reference')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['isic4_code', 'size_category', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nitaqat_thresholds');
    }
};
