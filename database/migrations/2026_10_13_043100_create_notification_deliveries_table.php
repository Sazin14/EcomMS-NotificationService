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
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->nullable()->constrained('notifications')->cascadeOnDelete();
            $table->foreignId('campaign_recipient_id')->nullable()->constrained('notification_campaign_recipients')->cascadeOnDelete();
            $table->string('channel', 20); // email, sms, push
            $table->string('provider', 60)->nullable();
            $table->string('provider_message_id', 255)->nullable();
            $table->string('destination', 320);
            $table->string('status', 20)->default('queued'); // queued, processing, sent, delivered, failed, suppressed
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestampTz('next_attempt_at')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->jsonb('provider_response')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'next_attempt_at'], 'notification_deliveries_retry_idx');
            $table->index(['notification_id', 'channel'], 'notification_deliveries_notification_channel_idx');
            $table->index(['campaign_recipient_id', 'channel'], 'notification_deliveries_campaign_channel_idx');
            $table->index(['provider', 'provider_message_id'], 'notification_deliveries_provider_message_idx');
        });
        DB::statement("ALTER TABLE notification_deliveries ADD CONSTRAINT notification_deliveries_one_source_chk CHECK ((notification_id IS NOT NULL AND campaign_recipient_id IS NULL) OR (notification_id IS NULL AND campaign_recipient_id IS NOT NULL))");

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
