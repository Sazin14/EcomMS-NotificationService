<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

/**
 * Default English wording. Uses firstOrCreate, so running it again never overwrites what staff have edited.
 * Placeholders: {{ order_number }} {{ customer.name }} {{ total_formatted }} {{ items_text }} {{ shipping_line }} ...
 * (every field of the event is available, see docs/EVENTS-notification.md)
 */
class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $sign = "\n\nThank you for shopping with us.";
        $hi = "Hi {{ customer.name }},\n\n";

        $templates = [
            'order.placed' => [
                'in_app' => ['Order {{ order_number }} received', 'We received your order {{ order_number }} ({{ total_formatted }}). We will confirm it shortly.'],
                'email' => ['We received your order {{ order_number }}', $hi."Thanks for your order {{ order_number }}.\n\n{{ items_text }}\n\nTotal: {{ total_formatted }}\nPayment: {{ payment_method_label }}\nDeliver to: {{ shipping_line }}".$sign],
            ],
            'order.placed.staff' => [
                'in_app' => ['New order {{ order_number }}', '{{ customer.name }} placed {{ order_number }} for {{ total_formatted }} ({{ payment_method_label }}).'],
            ],
            'order.confirmed' => [
                'in_app' => ['Order {{ order_number }} confirmed', 'Your order {{ order_number }} is confirmed and is being prepared.'],
                'email' => ['Your order {{ order_number }} is confirmed', $hi."Good news: your order {{ order_number }} is confirmed and we are preparing it.\n\n{{ items_text }}".$sign],
            ],
            'order.shipped' => [
                'in_app' => ['Order {{ order_number }} shipped', 'Your order {{ order_number }} is on its way with {{ carrier }} (tracking {{ tracking_number }}).'],
                'email' => ['Your order {{ order_number }} has shipped', $hi."Your order {{ order_number }} is on its way.\nCarrier: {{ carrier }}\nTracking number: {{ tracking_number }}\nDeliver to: {{ shipping_line }}".$sign],
            ],
            'order.delivered' => [
                'in_app' => ['Order {{ order_number }} delivered', 'Your order {{ order_number }} was delivered. We hope you love it.'],
                'email' => ['Your order {{ order_number }} was delivered', $hi."Your order {{ order_number }} has been delivered. If anything is wrong, you can request a return from your account.".$sign],
            ],
            'order.delivery_failed' => [
                'in_app' => ['Delivery problem with {{ order_number }}', 'We could not deliver {{ order_number }}: {{ reason }}. We will contact you.'],
                'email' => ['We could not deliver your order {{ order_number }}', $hi."The delivery of {{ order_number }} did not succeed.\nReason: {{ reason }}\n\nPlease keep your phone available; we will contact you to arrange another attempt.".$sign],
            ],
            'order.cancelled' => [
                'in_app' => ['Order {{ order_number }} cancelled', 'Your order {{ order_number }} was cancelled. {{ reason }}'],
                'email' => ['Your order {{ order_number }} was cancelled', $hi."Your order {{ order_number }} has been cancelled.\n{{ reason }}\n\nIf you already paid, the refund will follow in a separate message."],
            ],
            'order.preorder_ready' => [
                'in_app' => ['Your pre-order is ready', 'Stock for {{ order_number }} has arrived. Please complete the payment before {{ pay_before_formatted }}.'],
                'email' => ['Your pre-order {{ order_number }} is ready', $hi."The items you pre-ordered ({{ order_number }}) have arrived.\n\n{{ items_text }}\n\nPlease complete the payment before {{ pay_before_formatted }}, otherwise the stock is released to other customers.".$sign],
            ],
            'payment.paid' => [
                'in_app' => ['Payment received', 'We received your payment for {{ order_number }}.'],
                'email' => ['Payment received for {{ order_number }}', $hi."We received your payment of {{ total_formatted }} for order {{ order_number }}.\nReference: {{ reference }}".$sign],
            ],
            'payment.failed' => [
                'in_app' => ['Payment failed', 'The payment for {{ order_number }} failed: {{ reason }}. Please try again.'],
                'email' => ['Payment failed for {{ order_number }}', $hi."The payment for order {{ order_number }} did not go through.\nReason: {{ reason }}\n\nPlease try again from your account before the hold on your items expires."],
            ],
            'return.requested' => [
                'in_app' => ['Return {{ return_number }} requested', 'We received your return request {{ return_number }} for order {{ order_number }}.'],
            ],
            'return.requested.staff' => [
                'in_app' => ['Return request {{ return_number }}', '{{ customer.name }} requested a return for {{ order_number }}: {{ reason }}'],
            ],
            'return.received' => [
                'in_app' => ['Return {{ return_number }} received', 'We received your returned items for {{ order_number }}. Your refund is being processed.'],
                'email' => ['We received your return {{ return_number }}', $hi."We received the items you returned for order {{ order_number }}.\n{{ items_text }}\n\nYour refund is being processed.".$sign],
            ],
            'refund.processed' => [
                'in_app' => ['Refund of {{ amount_formatted }}', 'Your refund of {{ amount_formatted }} for {{ order_number }} was processed.'],
                'email' => ['Your refund for {{ order_number }}', $hi."Your refund of {{ amount_formatted }} for order {{ order_number }} was processed ({{ method }}).\nReference: {{ reference }}\n\nIt can take a few days to appear on your statement."],
            ],
            'inventory.stock.low' => [
                'in_app' => ['Low stock: {{ sku }}', '{{ sku }} has {{ available }} left at {{ warehouse }} (reorder level {{ reorder_level }}).'],
                'email' => ['Low stock: {{ sku }}', "{{ sku }} is running low.\nAvailable: {{ available }}\nReorder level: {{ reorder_level }}\nWarehouse: {{ warehouse }}"],
            ],
            'inventory.stock.out' => [
                'in_app' => ['Out of stock: {{ sku }}', '{{ sku }} is out of stock at {{ warehouse }}.'],
                'email' => ['Out of stock: {{ sku }}', "{{ sku }} is out of stock.\nWarehouse: {{ warehouse }}"],
            ],
        ];

        foreach ($templates as $key => $channels) {
            foreach ($channels as $channel => [$subject, $body]) {
                NotificationTemplate::firstOrCreate(
                    ['key' => $key, 'channel' => $channel, 'locale' => 'en'],
                    ['subject' => $subject, 'body' => $body, 'is_active' => true],
                );
            }
        }
    }
}
