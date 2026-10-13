<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\NotificationCampaignRecipient;
use App\Models\NotificationDelivery;
use Illuminate\Support\Facades\DB;

class NotificationDeliveryService
{
    public function queueForNotification(Notification $notification, string $channel, string $destination, string $provider): NotificationDelivery
    {
        return DB::transaction(fn () => NotificationDelivery::query()->firstOrCreate([
            'notification_id' => $notification->getKey(),
            'campaign_recipient_id' => null,
            'channel' => $channel,
        ], [
            'provider' => $provider,
            'destination' => $destination,
            'status' => 'queued',
            'attempt_count' => 0,
        ]));
    }

    /**
     * @throws \Throwable
     */
    public function queueForCampaignRecipient(NotificationCampaignRecipient $recipient, string $channel, string $provider): NotificationDelivery
    {
        return DB::transaction(fn () => NotificationDelivery::query()->firstOrCreate([
            'notification_id' => null,
            'campaign_recipient_id' => $recipient->getKey(),
            'channel' => $channel,
        ], [
            'provider' => $provider,
            'destination' => $recipient->destination,
            'status' => 'queued',
            'attempt_count' => 0,
        ]));
    }
}
