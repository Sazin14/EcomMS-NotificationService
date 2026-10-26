<?php

/*
|--------------------------------------------------------------------------
| Who is told what
|--------------------------------------------------------------------------
| event type (RabbitMQ routing key) => list of rules. One event can notify several audiences.
|
|   to          customer | staff
|   channels    in_app, email   (sms / push later)
|   critical    true = the customer cannot switch the email off (payment, cancellation, refund ...)
|   permission  staff only: everybody holding this permission sees the in-app notification
|   template    template key; default = the event type
*/

return [

    'timezone' => env('NOTIFICATION_TIMEZONE', 'Asia/Dhaka'),

    // Staff alert e-mails (stock low / out ...), comma separated. Empty = in-app only.
    'staff_alert_emails' => array_values(array_filter(array_map('trim', explode(',', (string) env('STAFF_ALERT_EMAILS', ''))))),

    // Old rows are deleted by `php artisan notifications:prune` (scheduled daily).
    'keep_days' => 90,

    'rules' => [

        // ---- OrderService -------------------------------------------------------------------------------------
        'order.placed' => [
            ['to' => 'customer', 'channels' => ['in_app', 'email'], 'critical' => true],
            ['to' => 'staff', 'permission' => 'order.view', 'channels' => ['in_app'], 'template' => 'order.placed.staff'],
        ],
        'order.confirmed' => [
            ['to' => 'customer', 'channels' => ['in_app', 'email']],
        ],
        'order.shipped' => [
            ['to' => 'customer', 'channels' => ['in_app', 'email']],
        ],
        'order.delivered' => [
            ['to' => 'customer', 'channels' => ['in_app', 'email']],
        ],
        'order.delivery_failed' => [
            ['to' => 'customer', 'channels' => ['in_app', 'email'], 'critical' => true],
        ],
        'order.cancelled' => [
            ['to' => 'customer', 'channels' => ['in_app', 'email'], 'critical' => true],
        ],
        'order.preorder_ready' => [
            ['to' => 'customer', 'channels' => ['in_app', 'email'], 'critical' => true],
        ],
        'payment.paid' => [
            ['to' => 'customer', 'channels' => ['in_app', 'email'], 'critical' => true],
        ],
        'payment.failed' => [
            ['to' => 'customer', 'channels' => ['in_app', 'email'], 'critical' => true],
        ],
        'return.requested' => [
            ['to' => 'customer', 'channels' => ['in_app']],
            ['to' => 'staff', 'permission' => 'return.manage', 'channels' => ['in_app'], 'template' => 'return.requested.staff'],
        ],
        'return.received' => [
            ['to' => 'customer', 'channels' => ['in_app', 'email']],
        ],
        'refund.processed' => [
            ['to' => 'customer', 'channels' => ['in_app', 'email'], 'critical' => true],
        ],

        // ---- InventoryService ---------------------------------------------------------------------------------
        'inventory.stock.low' => [
            ['to' => 'staff', 'permission' => 'inventory.view', 'channels' => ['in_app', 'email']],
        ],
        'inventory.stock.out' => [
            ['to' => 'staff', 'permission' => 'inventory.view', 'channels' => ['in_app', 'email']],
        ],
    ],
];
