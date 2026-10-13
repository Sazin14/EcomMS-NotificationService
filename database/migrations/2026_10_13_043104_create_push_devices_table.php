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
        Schema::create('push_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_recipient_id')->constrained('notification_recipients')->cascadeOnDelete();
            $table->string('provider', 40)->default('fcm');
            $table->text('device_token');
            $table->string('platform', 20); // android, ios, web
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();
            // Store a SHA-256 token hash for uniqueness; never index an arbitrarily long token column.
            $table->char('device_token_hash', 64)->unique();
            $table->index(['notification_recipient_id', 'revoked_at'], 'push_devices_recipient_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_devices');
    }
};
