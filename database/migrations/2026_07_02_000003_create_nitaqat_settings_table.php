<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nitaqat_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->string('label_ar');
            $table->string('description_ar')->nullable();
            $table->date('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nitaqat_settings');
    }
};
