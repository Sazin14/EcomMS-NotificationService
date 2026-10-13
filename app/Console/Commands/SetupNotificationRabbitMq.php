<?php

namespace App\Console\Commands;

use App\Services\Messaging\RabbitMqConnectionFactory;
use Illuminate\Console\Command;
use PhpAmqpLib\Wire\AMQPTable;
use Throwable;

class SetupNotificationRabbitMq extends Command
{
    protected $signature = 'notification:rabbitmq-setup';
    protected $description = 'Declare NotificationService RabbitMQ queues and bindings';

    public function handle(RabbitMqConnectionFactory $factory): int
    {
        $connection = $factory->connect();
        $channel = $connection->channel();
        $config = config('notification.rabbitmq');
        $channel->exchange_declare($config['exchange'], 'topic', false, true, false);

        foreach ($config['queues'] as $source => $queueName) {
            // This starter sends rejected messages to a durable DLQ. Align retry topology with
            // the exact retry/DLQ pattern already used by InventoryService before production.
            $dlqName = $queueName . '.dlq';
            $channel->queue_declare($dlqName, false, true, false, false);
            $arguments = new AMQPTable([
                'x-dead-letter-exchange' => $config['exchange'],
                'x-dead-letter-routing-key' => $dlqName,
            ]);
            $channel->queue_declare($queueName, false, true, false, false, false, $arguments);
            $channel->queue_bind($dlqName, $config['exchange'], $dlqName);

            foreach ($config['bindings'][$source] ?? [] as $routingKey) {
                $channel->queue_bind($queueName, $config['exchange'], $routingKey);
            }
            $this->info("Declared {$queueName} and {$dlqName}");
        }

        $channel->close();
        $connection->close();
        return self::SUCCESS;
    }
}
