<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The record of "somebody should know this". Customers get their own rows; staff alerts are ONE row
        // per event, shown to everybody who holds the `audience` permission (read state lives in notification_reads).
        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->string('dedupe_key', 120)->unique();             // "<event id>:<rule number>": the same event never notifies twice
            $t->string('recipient_type', 20);                    // customer | staff
            $t->unsignedBigInteger('recipient_id')->nullable();  // customer id; null for staff
            $t->string('audience', 80)->nullable();              // staff: the permission that may see it
            $t->string('event_type', 80);
            $t->boolean('in_app')->default(true);                // false = created only to carry e-mail deliveries
            $t->string('title', 255);
            $t->text('body');
            $t->jsonb('data')->nullable();                       // order_number, return_number, event_id ... for the frontend link
            $t->timestampTz('read_at')->nullable();              // customers only
            $t->timestamps();

            $t->index(['recipient_type', 'recipient_id', 'read_at']);
            $t->index(['recipient_type', 'audience', 'id']);
            $t->index('event_type');
            $t->index('created_at');
        });

        Schema::create('notification_reads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
            $t->unsignedBigInteger('staff_id');
            $t->timestampTz('read_at');
            $t->unique(['notification_id', 'staff_id']);
        });

        // One row per message actually sent (or skipped) through a channel. The text is a snapshot taken when
        // the event arrived, so a retry later sends exactly what was meant, even if the template changed.
        Schema::create('notification_deliveries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
            $t->string('channel', 20);                           // email (sms, push later)
            $t->string('address', 190)->nullable();
            $t->string('subject', 255)->nullable();
            $t->text('body');
            $t->string('status', 20)->default('pending')->index();   // pending | sent | failed | skipped
            $t->unsignedSmallInteger('attempts')->default(0);
            $t->text('last_error')->nullable();                  // also the reason for "skipped"
            $t->string('provider_message_id', 190)->nullable();
            $t->timestampTz('sent_at')->nullable();
            $t->timestamps();

            $t->index(['channel', 'status']);
        });

        Schema::create('notification_templates', function (Blueprint $t) {
            $t->id();
            $t->string('key', 100);                              // event type, e.g. order.placed
            $t->string('channel', 20);                           // in_app | email
            $t->string('locale', 10)->default('en');
            $t->string('subject', 255)->nullable();              // e-mail subject / in-app title
            $t->text('body');                                    // plain text with {{ placeholders }}
            $t->boolean('is_active')->default(true);
            $t->timestamps();

            $t->unique(['key', 'channel', 'locale']);
        });

        Schema::create('notification_preferences', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('customer_id');
            $t->string('channel', 20);
            $t->string('category', 20)->default('transactional');   // transactional | marketing
            $t->boolean('enabled')->default(true);
            $t->timestamps();

            $t->unique(['customer_id', 'channel', 'category']);
        });

        // Addresses that must never be written to (bounced, complained, unsubscribed).
        Schema::create('suppressions', function (Blueprint $t) {
            $t->id();
            $t->string('channel', 20);
            $t->string('address', 190);
            $t->string('reason', 100)->nullable();
            $t->timestamps();

            $t->unique(['channel', 'address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppressions');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notification_reads');
        Schema::dropIfExists('notifications');
    }
};
