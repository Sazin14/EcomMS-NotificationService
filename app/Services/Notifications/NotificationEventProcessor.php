<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\ProcessedMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class NotificationEventProcessor
{
    public function process(array $event): void
    {
        $eventId = $event['id'] ?? null;
        $eventType = $event['type'] ?? null;
        $producer = $event['producer'] ?? 'unknown';
        $data = $event['data'] ?? null;

        if (! is_string($eventId) || $eventId === '' || ! is_string($eventType) || ! is_array($data)) {
            throw new \InvalidArgumentException('Invalid event envelope: expected id, type, and data.');
        }

        DB::transaction(function () use ($eventId, $eventType, $producer, $data) {
            // A unique event_id constraint is required. Insert-or-ignore handles redelivery races.
            $inserted = DB::table('processed_messages')->insertOrIgnore([
                'event_id' => $eventId,
                'event_type' => $eventType,
                'source_service' => $producer,
                'status' => 'processing',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted === 0) {
                $existing = ProcessedMessage::query()->where('event_id', $eventId)->lockForUpdate()->first();
                if ($existing && $existing->status === 'processed') {
                    return;
                }
                if ($existing) {
                    $existing->forceFill(['status' => 'processing', 'last_error' => null])->save();
                } else {
                    // A conflicting insert must not be treated as safely processed without inspecting it.
                    throw new \RuntimeException("Could not claim event {$eventId} for processing.");
                }
            }

            $this->createNotifications($eventId, $eventType, $data);

            ProcessedMessage::query()->where('event_id', $eventId)->update([
                'status' => 'processed',
                'processed_at' => now(),
                'last_error' => null,
                'updated_at' => now(),
            ]);
        });
    }

    private function createNotifications(string $eventId, string $eventType, array $data): void
    {
        // Transactional events should carry a self-contained customer snapshot.
        $customer = $data['customer'] ?? null;
        if (is_array($customer) && isset($customer['id'])) {
            $this->createForSnapshot($eventId, 'customer', (int) $customer['id'], $eventType, $data, $customer);
        }

        // Inventory staff targeting is a temporary configured ID list. Replace with your
        // AuthService permission-cache client to target only staff with inventory.view.
        if (str_starts_with($eventType, 'inventory.stock.') || str_starts_with($eventType, 'inventory.preorder.')) {
            $ids = config('notification.inventory_staff_ids', []);
            if ($ids) {
                $staff = NotificationRecipient::query()
                    ->where('recipient_type', 'staff')
                    ->whereIn('external_id', $ids)
                    ->where('active', true)
                    ->get();

                foreach ($staff as $recipient) {
                    $this->createForSnapshot($eventId, 'staff', (int) $recipient->external_id, $eventType, $data, [
                        'id' => $recipient->external_id,
                        'name' => $recipient->name,
                        'email' => $recipient->email,
                        'phone' => $recipient->phone,
                        'language' => $recipient->locale,
                    ]);
                }
            }
        }
    }

    private function createForSnapshot(string $eventId, string $recipientType, int $recipientId, string $eventType, array $data, array $snapshot): void
    {
        $copy = $this->messageFor($eventType, $data, $snapshot);

        // Unique constraint recommended: (event_id, recipient_type, recipient_id, type).
        // Event redelivery is already guarded by processed_messages.
        Notification::query()->firstOrCreate([
            'event_id' => $eventId,
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'type' => $eventType,
        ], [
            'title' => $copy['title'],
            'body' => $copy['body'],
            'data' => $copy['data'],
            'priority' => $copy['priority'],
        ]);
    }

    private function messageFor(string $eventType, array $data, array $snapshot): array
    {
        $label = Str::headline(str_replace('.', ' ', $eventType));
        $reference = $data['order_number'] ?? $data['sku'] ?? $data['variant_id'] ?? null;
        $body = $reference ? "{$label}: {$reference}" : $label;

        return [
            'title' => $label,
            'body' => $body,
            'data' => ['event_type' => $eventType, 'payload' => $data],
            'priority' => str_contains($eventType, '.failed') || str_contains($eventType, '.out') ? 'high' : 'normal',
        ];
    }
}
