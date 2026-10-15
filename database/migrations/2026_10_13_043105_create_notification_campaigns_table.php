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
        Schema::create('notification_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name', 180);
            $table->string('channel', 20)->default('email');
            $table->string('subject', 255)->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->jsonb('audience')->nullable();
            $table->string('status', 24)->default('draft'); // draft, pending_approval, scheduled, processing, paused, completed, cancelled, failed
            $table->foreignId('created_by_recipient_id')->constrained('notification_recipients')->restrictOnDelete();
            $table->foreignId('approved_by_recipient_id')->nullable()->constrained('notification_recipients')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('scheduled_at')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('cancel_requested_at')->nullable();
            $table->jsonb('content_snapshot')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'scheduled_at'], 'notification_campaigns_status_schedule_idx');
            $table->index(['created_by_recipient_id', 'created_at'], 'notification_campaigns_creator_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_campaigns');
    }
};
