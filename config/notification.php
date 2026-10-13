<?php

return [
    'rabbitmq' => [
        'host' => env('RABBITMQ_HOST', '127.0.0.1'),
        'port' => (int) env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
        'exchange' => env('RABBITMQ_EXCHANGE', 'ecom.events'),
        'queues' => [
            'inventory' => env('NOTIFICATION_INVENTORY_QUEUE', 'notification.inventory'),
            'order' => env('NOTIFICATION_ORDER_QUEUE', 'notification.order'),
            'auth' => env('NOTIFICATION_AUTH_QUEUE', 'notification.auth'),
        ],
        'bindings' => [
            'inventory' => ['inventory.stock.*', 'inventory.preorder.*'],
            'order' => ['order.*', 'payment.*', 'return.*', 'refund.*'],
            'auth' => ['customer.*', 'staff.*'],
        ],
    ],

    // Temporary bootstrap audience for inventory alerts. Replace with your AuthService
    // authorization-cache lookup for the inventory.view permission.
    'inventory_staff_ids' => array_values(array_filter(array_map(
        'intval', explode(',', (string) env('NOTIFICATION_INVENTORY_STAFF_IDS', ''))
    ))),
];
