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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_recipient_id')->constrained('notification_recipients')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->string('category', 40); // transactional, marketing, system
            $table->boolean('is_enabled')->default(true);
            $table->timestampTz('consented_at')->nullable();
            $table->string('consent_source', 80)->nullable();
            $table->timestampsTz();
            $table->unique(['notification_recipient_id', 'channel', 'category'], 'notification_preferences_recipient_channel_category_uq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
