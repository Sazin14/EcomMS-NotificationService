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
        Schema::create('notification_suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 20); // email or sms
            $table->string('destination_normalized', 320);
            $table->string('reason', 40); // unsubscribe, hard_bounce, complaint, invalid_destination, manual_block
            $table->string('source', 80)->nullable();
            $table->timestampTz('suppressed_at')->useCurrent();
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();
            $table->unique(['channel', 'destination_normalized'], 'notification_suppressions_destination_uq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_suppressions');
    }
};
