<?php

namespace App\Services\Notifications;

use App\Models\NotificationRecipient;
use Illuminate\Support\Arr;

class NotificationRecipientService
{
    /** Upsert a contact snapshot received from an authoritative AuthService event. */
    public function syncFromEvent(array $data): NotificationRecipient
    {
        $type = $data['recipient_type'] ?? null;
        $externalId = $data['recipient_id'] ?? null;

        if (! in_array($type, ['customer', 'staff'], true) || ! is_numeric($externalId)) {
            throw new \InvalidArgumentException('Recipient sync event requires recipient_type and numeric recipient_id.');
        }

        // Requires a unique index on (recipient_type, external_id) in notification_recipients.
        $recipient = NotificationRecipient::query()->firstOrNew([
            'recipient_type' => $type,
            'external_id' => (int) $externalId,
        ]);

        $recipient->fill(Arr::only($data, [
            'name', 'email', 'phone', 'language', 'active', 'marketing_consent_at',
        ]));
        $recipient->save();

        return $recipient;
    }
}
