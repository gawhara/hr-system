<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks which alert thresholds (from document_types.alert_days) have
     * already fired per document, so the scheduled job never re-sends the
     * same alert — including after offline periods where several thresholds
     * may have been crossed at once.
     */
    public function up(): void
    {
        Schema::create('document_expiry_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('threshold_days');
            $table->timestamp('notified_at');
            $table->timestamps();

            $table->unique(['employee_document_id', 'threshold_days'], 'doc_threshold_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_expiry_alerts');
    }
};
