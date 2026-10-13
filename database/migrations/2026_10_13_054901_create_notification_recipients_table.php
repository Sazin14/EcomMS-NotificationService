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
        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('identity_type', 20); // customer or staff; identity owned by AuthService
            $table->unsignedBigInteger('identity_id');
            $table->string('name', 180)->nullable();
            $table->string('email', 320)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('locale', 12)->default('en');
            $table->boolean('is_active')->default(true);
            $table->timestampTz('marketing_consent_at')->nullable();
            $table->timestampTz('source_updated_at')->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->timestampsTz();
            $table->unique(['identity_type', 'identity_id'], 'notification_recipients_identity_uq');
            $table->index(['is_active', 'identity_type'], 'notification_recipients_active_type_idx');
            $table->index('email', 'notification_recipients_email_idx');
            $table->index('phone', 'notification_recipients_phone_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_recipients');
    }
};
