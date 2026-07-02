<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Arabic is the system default; English is an explicit, remembered
     * per-user opt-in (AGENT.md — Language & Localization).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->default('ar')->after('current_company_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
