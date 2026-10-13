<?php

namespace App\Services\Notifications;

use App\Models\NotificationPreference;
use App\Models\NotificationRecipient;
use App\Models\NotificationSuppression;

class NotificationSuppressionService
{
    public function canSend(NotificationRecipient $recipient, string $channel, string $category): bool
    {
        $destination = match ($channel) {
            'email' => $recipient->email,
            'sms' => $recipient->phone,
            default => null,
        };

        if (in_array($channel, ['email', 'sms'], true)) {
            if (! is_string($destination) || $destination === '') {
                return false;
            }

            if (NotificationSuppression::query()
                ->where('channel', $channel)
                ->whereRaw('LOWER(destination) = ?', [mb_strtolower(trim($destination))])
                ->exists()) {
                return false;
            }
        }

        // Marketing must have explicit consent; a preference toggle alone is not enough.
        if ($category === 'marketing' && ! $recipient->marketing_consent_at) {
            return false;
        }

        $preference = NotificationPreference::query()
            ->where('recipient_type', $recipient->recipient_type)
            ->where('recipient_id', $recipient->external_id)
            ->where('category', $category)
            ->where('channel', $channel)
            ->first();

        return $preference?->is_enabled ?? ($category !== 'marketing');
    }
}
