<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * national_id, iban and passport_id move to encrypted-at-rest storage
     * (Laravel `encrypted` cast). Ciphertext is randomized, so uniqueness and
     * exact-match search move to national_id_hash (SHA-256 of the plaintext).
     * Salary columns intentionally stay plaintext: payroll math, dashboard
     * aggregation and Nitaqat salary thresholds run in SQL — rely on
     * database/tablespace encryption for those instead.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['national_id']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->text('national_id')->nullable()->change();
            $table->text('iban')->nullable()->change();
            $table->text('passport_id')->nullable()->change();
            $table->string('national_id_hash', 64)->nullable()->unique()->after('national_id');
        });

        // Encrypt pre-existing plaintext rows (skip anything already encrypted).
        DB::table('employees')
            ->select(['id', 'national_id', 'iban', 'passport_id'])
            ->orderBy('id')
            ->each(function ($row) {
                $updates = [];

                foreach (['national_id', 'iban', 'passport_id'] as $column) {
                    $value = $row->{$column};

                    if ($value !== null && ! str_starts_with($value, 'eyJpdiI6')) {
                        $updates[$column] = Crypt::encryptString($value);

                        if ($column === 'national_id') {
                            $updates['national_id_hash'] = hash('sha256', $value);
                        }
                    }
                }

                if ($updates !== []) {
                    DB::table('employees')->where('id', $row->id)->update($updates);
                }
            });
    }

    public function down(): void
    {
        DB::table('employees')
            ->select(['id', 'national_id', 'iban', 'passport_id'])
            ->orderBy('id')
            ->each(function ($row) {
                $updates = [];

                foreach (['national_id', 'iban', 'passport_id'] as $column) {
                    $value = $row->{$column};

                    if ($value !== null && str_starts_with($value, 'eyJpdiI6')) {
                        $updates[$column] = Crypt::decryptString($value);
                    }
                }

                if ($updates !== []) {
                    DB::table('employees')->where('id', $row->id)->update($updates);
                }
            });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['national_id_hash']);
            $table->dropColumn('national_id_hash');
            $table->string('national_id', 512)->nullable()->change();
            $table->string('iban', 512)->nullable()->change();
            $table->string('passport_id', 512)->nullable()->change();
        });
    }
};
