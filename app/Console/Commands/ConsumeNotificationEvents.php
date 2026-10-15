<?php

namespace App\Console\Commands;

use App\Services\Messaging\RabbitMqConnectionFactory;
use App\Services\Notifications\NotificationEventProcessor;
use Illuminate\Console\Command;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class ConsumeNotificationEvents extends Command
{
    protected $signature = 'notification:consume {source : inventory, order, or auth}';
    protected $description = 'Consume NotificationService business events from RabbitMQ';

    public function handle(RabbitMqConnectionFactory $factory, NotificationEventProcessor $processor): int
    {
        $source = (string) $this->argument('source');
        $queue = config("notification.rabbitmq.queues.{$source}");
        if (! $queue) {
            $this->error('Unknown source. Use inventory, order, or auth.');
            return self::FAILURE;
        }

        $connection = $factory->connect();
        $channel = $connection->channel();
        $channel->basic_qos(null, 1, null);
        $channel->basic_consume($queue, '', false, false, false, false,
            function (AMQPMessage $message) use ($processor): void {
                try {
                    $event = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);
                    if (! is_array($event)) {
                        throw new \InvalidArgumentException('Event payload must be a JSON object.');
                    }
                    $processor->process($event);
                    $message->ack();
                } catch (Throwable $exception) {
                    report($exception);
                    // Rejected messages are dead-lettered by the queue declaration.
                    // Add delayed retry queues matching InventoryService's existing pattern.
                    $message->nack(false, false);
                }
            }
        );

        $this->info("Consuming {$queue}; stop with Ctrl+C.");
        try {
            while ($channel->is_consuming()) {
                $channel->wait();
            }
        } finally {
            $channel->close();
            $connection->close();
        }

        return self::SUCCESS;
    }
}
