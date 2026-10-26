<?php

namespace App\Console\Commands;

use App\Support\RabbitMq\RabbitMqConnection;
use Illuminate\Console\Command;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Declares everything NotificationService needs inside RabbitMQ. Safe to run again.
 *   notification.order      <- order.#  payment.#  return.#  refund.#
 *   notification.inventory  <- inventory.stock.low  inventory.stock.out
 * each with a .retry queue (30 s wait) and a .dlq queue (parked for a human).
 * NotificationService publishes nothing; it only listens. Run this BEFORE the other services publish,
 * because an event published while no queue is bound to its routing key is dropped by RabbitMQ.
 */
class SetupRabbitMq extends Command
{
    protected $signature = 'rabbitmq:setup';

    protected $description = 'Create the RabbitMQ queues and bindings NotificationService needs';

    public function handle(RabbitMqConnection $mq): int
    {
        $channel = $mq->channel();   // declares the exchanges ecom.events and ecom.retry

        $this->info('Exchanges ready: '.config('rabbitmq.exchange').', '.config('rabbitmq.retry_exchange'));

        foreach (config('rabbitmq.queues') as $queue => $bindingKeys) {
            $this->declareQueue($channel, $queue, $bindingKeys);
            $this->info("Queues ready: {$queue}, {$queue}.retry, {$queue}.dlq  (binding: ".implode(', ', $bindingKeys).')');
        }

        $mq->close();

        return self::SUCCESS;
    }

    /** main queue + retry queue (waits, then sends the message back) + dead-letter queue (parked for a human) */
    private function declareQueue(AMQPChannel $channel, string $queue, array $bindingKeys): void
    {
        $exchange = config('rabbitmq.exchange');
        $retryExchange = config('rabbitmq.retry_exchange');

        $channel->queue_declare($queue, false, true, false, false, false, new AMQPTable([
            'x-dead-letter-exchange' => $retryExchange,
            'x-dead-letter-routing-key' => $queue,
        ]));

        foreach ($bindingKeys as $key) {
            $channel->queue_bind($queue, $exchange, $key);
        }

        $channel->queue_declare("{$queue}.retry", false, true, false, false, false, new AMQPTable([
            'x-message-ttl' => (int) config('rabbitmq.retry_delay_ms'),
            'x-dead-letter-exchange' => '',
            'x-dead-letter-routing-key' => $queue,
        ]));
        $channel->queue_bind("{$queue}.retry", $retryExchange, $queue);

        $channel->queue_declare("{$queue}.dlq", false, true, false, false, false);
    }
}
