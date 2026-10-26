<?php

namespace App\Services\Events;

use App\Services\NotificationRouter;
use App\Support\RabbitMq\EventHandler;

/**
 * Handles every event of the queues notification.order and notification.inventory.
 * The consumer command already skips an event id it has processed before; the router also refuses to create the
 * same notification twice. An exception here sends the message to the retry queue (30 s), then the dead-letter queue.
 */
class NotificationEventHandler implements EventHandler
{
    public function __construct(private NotificationRouter $router) {}

    public function handle(array $envelope): void
    {
        $this->router->route($envelope);
    }
}
