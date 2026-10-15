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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_recipient_id')->constrained('notification_recipients')->cascadeOnDelete();
            $table->foreignId('processed_message_id')->nullable()->constrained('processed_messages')->restrictOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
            $table->string('type', 120); // order.placed, return.approved, refund.completed, inventory.stock.low
            $table->string('title', 255);
            $table->text('body');
            $table->string('link_url', 2048)->nullable();
            $table->jsonb('data')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->timestampTz('read_at')->nullable();
            $table->timestampsTz();
            $table->unique(['processed_message_id', 'notification_recipient_id', 'type'], 'notifications_event_recipient_type_uq');
            $table->index(['notification_recipient_id', 'created_at'], 'notifications_recipient_created_idx');
            $table->index(['notification_recipient_id', 'read_at'], 'notifications_recipient_read_idx');
            $table->index(['type', 'created_at'], 'notifications_type_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
