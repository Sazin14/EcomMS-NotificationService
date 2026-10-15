<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('processed_messages', function (Blueprint $table) {
            $table->id();
            $table->string('source_service', 80);
            $table->string('event_id', 160);
            $table->string('event_type', 160);
            $table->string('status', 20)->default('processing'); // processing, processed, failed
            $table->jsonb('payload')->nullable();
            $table->timestampTz('received_at')->useCurrent();
            $table->timestampTz('processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestampsTz();
            $table->unique(['source_service', 'event_id'], 'processed_messages_source_event_uq');
            $table->index(['status', 'received_at'], 'processed_messages_status_received_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processed_messages');
    }
};
