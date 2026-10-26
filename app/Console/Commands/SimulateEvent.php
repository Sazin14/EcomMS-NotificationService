<?php

namespace App\Console\Commands;

use App\Services\Events\NotificationEventHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Test without the other services or RabbitMQ: builds a realistic event and runs it through the same handler.
 *   php artisan notifications:simulate order.placed --customer=1 --email=me@example.com
 *   php artisan notifications:simulate inventory.stock.low
 * Then:  php artisan queue:work --queue=notifications --once   (sends the e-mail; MAIL_MAILER=log writes it to storage/logs)
 */
class SimulateEvent extends Command
{
    protected $signature = 'notifications:simulate {type} {--customer=1} {--email=customer@example.com} {--name=Test Customer} {--order=ORD-TEST0001}';

    protected $description = 'Feed a sample event to the notification handler (development only)';

    public function handle(NotificationEventHandler $handler): int
    {
        if (app()->isProduction()) {
            $this->error('Not available in production.');

            return self::FAILURE;
        }

        $type = (string) $this->argument('type');

        $envelope = [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'version' => 1,
            'occurred_at' => now()->toIso8601ZuluString('microsecond'),
            'producer' => str_starts_with($type, 'inventory.') ? 'inventory-service' : 'order-service',
            'data' => $this->data($type),
        ];

        DB::transaction(fn () => $handler->handle($envelope));

        $this->info("Handled {$type} ({$envelope['id']}).");

        return self::SUCCESS;
    }

    private function data(string $type): array
    {
        if (str_starts_with($type, 'inventory.')) {
            return ['variant_id' => 12, 'sku' => 'NX1-8GB-128GB-SPACEGREY', 'warehouse' => 'Main', 'available' => 3, 'reorder_level' => 10];
        }

        $order = [
            'order_number' => $this->option('order'), 'type' => 'standard', 'status' => 'pending',
            'placed_at' => now()->toIso8601ZuluString(), 'payment_method' => 'cod', 'payment_status' => 'pending',
            'currency' => 'BDT', 'subtotal' => '1250.00', 'shipping_fee' => '60.00', 'discount_total' => '0.00', 'total' => '1310.00',
            'customer' => [
                'id' => (int) $this->option('customer'), 'name' => $this->option('name'),
                'email' => $this->option('email'), 'phone' => '+8801700000000', 'locale' => 'en',
            ],
            'shipping' => ['name' => $this->option('name'), 'phone' => '+8801700000000', 'address' => 'House 1, Road 2', 'city' => 'Dhaka', 'postcode' => '1207', 'country' => 'BD'],
            'items' => [['variant_id' => 12, 'sku' => 'NX1-8GB-128GB-SPACEGREY', 'name' => 'Nova X1', 'variant_label' => '8GB / 128GB / Space Grey', 'quantity' => 1, 'unit_price' => '1250.00', 'line_total' => '1250.00']],
        ];

        return $order + match ($type) {
            'order.shipped' => ['carrier' => 'Pathao', 'tracking_number' => 'T123456'],
            'order.cancelled' => ['reason' => 'Not confirmed or paid in time.'],
            'order.delivery_failed' => ['reason' => 'Customer not reachable.'],
            'order.preorder_ready' => ['pay_before' => now()->addHours(48)->toIso8601ZuluString()],
            'payment.paid' => ['reference' => 'SBX-ABC123'],
            'payment.failed' => ['reason' => 'Card declined.'],
            'return.requested', 'return.received' => ['return_number' => 'RET-20261026-ABC123', 'reason' => 'Wrong size', 'items' => [['variant_id' => 12, 'quantity' => 1, 'condition' => 'resellable']]],
            'refund.processed' => ['amount' => '1250.00', 'method' => 'original', 'reference' => 'SBX-RF-XYZ'],
            default => [],
        };
    }
}
