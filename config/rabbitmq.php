<?php

return [
    'host' => env('RABBITMQ_HOST', '127.0.0.1'),
    'port' => (int) env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost' => env('RABBITMQ_VHOST', 'ecom'),
    'ssl' => (bool) env('RABBITMQ_SSL', false),   // true in production (amqps, port 5671)

    // Events of every service travel through ONE topic exchange.
    'exchange' => 'ecom.events',
    // Failed messages wait here (a queue with a TTL) and then go back to the main queue.
    'retry_exchange' => 'ecom.retry',

    'retry_delay_ms' => 30000,   // wait before a failed message is tried again
    'max_attempts' => 5,         // after this many failures the message is parked in <queue>.dlq

    // queue name => class that handles its events (must implement App\Support\RabbitMq\EventHandler)
    'consumers' => [
        'notification.order' => App\Services\Events\NotificationEventHandler::class,
        'notification.inventory' => App\Services\Events\NotificationEventHandler::class,
    ],

    // queue name => routing keys bound to it (used by `php artisan rabbitmq:setup`)
    'queues' => [
        'notification.order' => ['order.#', 'payment.#', 'return.#', 'refund.#'],
        'notification.inventory' => ['inventory.stock.low', 'inventory.stock.out'],
    ],
];
