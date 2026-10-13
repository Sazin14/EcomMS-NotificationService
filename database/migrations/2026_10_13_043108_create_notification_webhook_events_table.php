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
        Schema::create('notification_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 60);
            $table->string('provider_event_id', 255);
            $table->string('event_type', 120);
            $table->jsonb('payload');
            $table->timestampTz('received_at')->useCurrent();
            $table->timestampTz('processed_at')->nullable();
            $table->text('processing_error')->nullable();
            $table->timestampsTz();
            $table->unique(['provider', 'provider_event_id'], 'notification_webhook_provider_event_uq');
            $table->index(['processed_at', 'received_at'], 'notification_webhook_processing_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_webhook_events');
    }
};
