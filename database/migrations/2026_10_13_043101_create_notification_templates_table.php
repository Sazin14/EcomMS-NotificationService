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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120);
            $table->string('channel', 20); // email, sms, push, in_app
            $table->string('locale', 12)->default('en');
            $table->string('category', 40); // transactional, marketing, system
            $table->string('subject', 255)->nullable();
            $table->text('body');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->unique(['key', 'channel', 'locale', 'version'], 'notification_templates_version_uq');
            $table->index(['key', 'channel', 'locale', 'is_active'], 'notification_templates_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
