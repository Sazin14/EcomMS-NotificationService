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
        Schema::create('notification_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('notification_campaigns')->cascadeOnDelete();
            $table->foreignId('notification_recipient_id')->constrained('notification_recipients')->restrictOnDelete();
            $table->string('destination', 320); // address snapshot for this campaign
            $table->jsonb('personalization_data')->nullable();
            $table->string('status', 20)->default('pending'); // pending, queued, skipped, completed, failed
            $table->string('skip_reason', 120)->nullable();
            $table->timestampTz('queued_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
            $table->unique(['campaign_id', 'notification_recipient_id'], 'notification_campaign_recipient_uq');
            $table->index(['campaign_id', 'status'], 'notification_campaign_recipient_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_campaign_recipients');
    }
};
