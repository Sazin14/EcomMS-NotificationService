<?php

namespace App\Services\Notifications;

/** Maps business event types to channels/templates/audiences. Keep rules here or move them to DB-backed rules later. */
class NotificationRoutingService
{
    public function ruleFor(string $eventType): array
    {
        return match (true) {
            str_starts_with($eventType, 'order.'), str_starts_with($eventType, 'payment.'),
            str_starts_with($eventType, 'return.'), str_starts_with($eventType, 'refund.') => [
                'category' => 'transactional', 'channels' => ['email', 'in_app'], 'audience' => 'event_customer_snapshot',
            ],
            str_starts_with($eventType, 'inventory.stock.'), str_starts_with($eventType, 'inventory.preorder.') => [
                'category' => 'system', 'channels' => ['in_app', 'email'], 'audience' => 'inventory_staff',
            ],
            str_starts_with($eventType, 'promotion.') => [
                'category' => 'marketing', 'channels' => ['email'], 'audience' => 'eligible_opted_in_customers',
            ],
            default => ['category' => 'system', 'channels' => ['in_app'], 'audience' => 'event_snapshot_if_present'],
        };
    }
}
