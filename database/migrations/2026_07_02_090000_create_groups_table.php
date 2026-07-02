<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('qiwa_entity_id')->nullable()->after('gosi_number');
            $table->string('unified_national_number')->nullable()->after('qiwa_entity_id');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
            $table->dropColumn(['qiwa_entity_id', 'unified_national_number']);
        });

        Schema::dropIfExists('groups');
    }
};
