<?php

namespace App\Jobs;

use App\Mail\NotificationMail;
use App\Models\NotificationDelivery;
use App\Models\Suppression;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends ONE e-mail delivery. Run the worker with:  php artisan queue:work --queue=notifications
 * Safe to run twice: a delivery that is not "pending" is left alone.
 */
class SendEmailDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public function __construct(public int $deliveryId)
    {
        $this->onQueue('notifications');
    }

    /** Seconds to wait before attempt 2, 3, 4. */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(): void
    {
        $delivery = NotificationDelivery::find($this->deliveryId);

        if (! $delivery || $delivery->status !== NotificationDelivery::PENDING) {
            return;
        }

        if (Suppression::where('channel', 'email')->where('address', $delivery->address)->exists()) {
            $delivery->update(['status' => NotificationDelivery::SKIPPED, 'last_error' => 'suppressed']);

            return;
        }

        $delivery->increment('attempts');

        Mail::to($delivery->address)->send(new NotificationMail((string) $delivery->subject, $delivery->body));

        $delivery->update(['status' => NotificationDelivery::SENT, 'sent_at' => now(), 'last_error' => null]);
    }

    /** Called after the last attempt failed. */
    public function failed(Throwable $e): void
    {
        NotificationDelivery::whereKey($this->deliveryId)->update([
            'status' => NotificationDelivery::FAILED,
            'last_error' => mb_substr($e->getMessage(), 0, 500),
        ]);
    }
}
