<?php

namespace App\Services\Messaging;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMqConnectionFactory
{
    public function connect(): AMQPStreamConnection
    {
        $config = config('notification.rabbitmq');

        return new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            $config['vhost'],
        );
    }
}
