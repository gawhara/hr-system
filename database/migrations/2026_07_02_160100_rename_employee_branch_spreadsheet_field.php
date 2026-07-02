<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('employees', 'branch') && ! Schema::hasColumn('employees', 'branch_text')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->renameColumn('branch', 'branch_text');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'branch_text') && ! Schema::hasColumn('employees', 'branch')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->renameColumn('branch_text', 'branch');
            });
        }
    }
};
