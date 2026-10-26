<?php

namespace App\Services;

use App\Jobs\SendEmailDelivery;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\NotificationPreference;
use App\Models\Suppression;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * event -> rules (config/notifications.php) -> notification rows + delivery rows -> e-mail jobs.
 * Called by the RabbitMQ consumer inside ONE database transaction together with the "event already processed" marker,
 * so a crash halfway leaves nothing behind and the event is simply retried. E-mail jobs are queued only after that
 * transaction commits.
 */
class NotificationRouter
{
    public function __construct(private TemplateRenderer $templates) {}

    public function route(array $envelope): void
    {
        $type = (string) ($envelope['type'] ?? '');
        $rules = config("notifications.rules.{$type}", []);

        if ($rules === []) {
            return;   // an event type we do not notify about (or a newer producer): ignore
        }

        $data = (array) ($envelope['data'] ?? []);
        $vars = $this->variables($type, $data);

        foreach ($rules as $index => $rule) {
            $dedupeKey = "{$envelope['id']}:{$index}";

            if (Notification::where('dedupe_key', $dedupeKey)->exists()) {
                continue;   // this rule already handled this event
            }

            $this->apply($envelope, $type, $rule, $dedupeKey, $data, $vars);
        }
    }

    private function apply(array $envelope, string $type, array $rule, string $dedupeKey, array $data, array $vars): void
    {
        $toCustomer = $rule['to'] === 'customer';
        $customerId = $toCustomer ? Arr::get($data, 'customer.id') : null;

        if ($toCustomer && ! $customerId) {
            Log::warning("{$type} {$envelope['id']} has no customer.id; customer notification skipped.");

            return;
        }

        $locale = (string) (Arr::get($data, 'customer.locale') ?: 'en');
        $key = $rule['template'] ?? $type;
        $channels = $rule['channels'];
        $inApp = in_array('in_app', $channels, true);

        // The notification row: in-app text if the rule has in_app, otherwise the e-mail subject.
        $text = $this->templates->build($key, $inApp ? 'in_app' : 'email', $locale, $vars);

        if (! $text) {
            Log::warning("No active template for [{$key}] ({$locale}); using a plain fallback.");
            $text = ['subject' => Str::headline($type), 'body' => ''];
        }

        $notification = Notification::create([
            'dedupe_key' => $dedupeKey,
            'recipient_type' => $toCustomer ? Notification::CUSTOMER : Notification::STAFF,
            'recipient_id' => $customerId ? (int) $customerId : null,
            'audience' => $toCustomer ? null : ($rule['permission'] ?? null),
            'event_type' => $type,
            'in_app' => $inApp,
            'title' => $text['subject'],
            'body' => $text['body'],
            'data' => array_filter([
                'event_id' => $envelope['id'],
                'order_number' => $data['order_number'] ?? null,
                'return_number' => $data['return_number'] ?? null,
                'variant_id' => $data['variant_id'] ?? null,
            ], fn ($v) => $v !== null),
        ]);

        if (in_array('email', $channels, true)) {
            $this->queueEmails($notification, $rule, $toCustomer, $key, $locale, $data, $vars, $customerId ? (int) $customerId : null);
        }
    }

    private function queueEmails(Notification $notification, array $rule, bool $toCustomer, string $key, string $locale, array $data, array $vars, ?int $customerId): void
    {
        $mail = $this->templates->build($key, 'email', $locale, $vars) ?? [
            'subject' => $notification->title,
            'body' => $notification->body,
        ];

        $addresses = $toCustomer
            ? [Arr::get($data, 'customer.email')]
            : (config('notifications.staff_alert_emails') ?: [null]);

        foreach (array_unique($addresses, SORT_REGULAR) as $address) {
            $address = $address ? Str::lower(trim((string) $address)) : null;
            $skip = $this->skipReason('email', $address, $customerId, (bool) ($rule['critical'] ?? false));

            if ($skip === 'no_address' && ! $toCustomer) {
                continue;   // staff alert with no e-mail list configured: in-app only, nothing to record
            }

            $delivery = $notification->deliveries()->create([
                'channel' => 'email',
                'address' => $address,
                'subject' => $mail['subject'],
                'body' => $mail['body'],
                'status' => $skip ? NotificationDelivery::SKIPPED : NotificationDelivery::PENDING,
                'last_error' => $skip,
            ]);

            if (! $skip) {
                SendEmailDelivery::dispatch($delivery->id)->afterCommit();
            }
        }
    }

    /** Why a message must not be sent (null = send it). */
    private function skipReason(string $channel, ?string $address, ?int $customerId, bool $critical): ?string
    {
        if (! $address) {
            return 'no_address';
        }

        if (Suppression::where('channel', $channel)->where('address', $address)->exists()) {
            return 'suppressed';
        }

        if ($customerId && ! $critical) {
            $enabled = NotificationPreference::where('customer_id', $customerId)
                ->where('channel', $channel)
                ->where('category', 'transactional')
                ->value('enabled');

            if ($enabled === false) {
                return 'opted_out';
            }
        }

        return null;
    }

    /** Everything a template may use: the event data itself plus a few ready-made texts. */
    private function variables(string $type, array $data): array
    {
        $tz = (string) config('notifications.timezone', 'UTC');

        $vars = $data + ['event_type' => $type];

        $vars['items_text'] = collect($data['items'] ?? [])->map(function ($i) {
            $name = $i['name'] ?? ('Item #'.($i['variant_id'] ?? '?'));
            $label = ! empty($i['variant_label']) ? " ({$i['variant_label']})" : '';
            $line = isset($i['line_total']) ? ' - '.($i['line_total']) : '';
            $cond = isset($i['condition']) ? " [{$i['condition']}]" : '';

            return '- '.($i['quantity'] ?? 1)." x {$name}{$label}{$line}{$cond}";
        })->implode("\n");

        $vars['payment_method_label'] = match ($data['payment_method'] ?? null) {
            'cod' => 'Cash on delivery',
            'online' => 'Online payment',
            default => (string) ($data['payment_method'] ?? ''),
        };

        $vars['total_formatted'] = trim(($data['currency'] ?? '').' '.($data['total'] ?? ''));
        $vars['amount_formatted'] = trim(($data['currency'] ?? '').' '.($data['amount'] ?? ''));
        $vars['shipping_line'] = collect([
            Arr::get($data, 'shipping.name'), Arr::get($data, 'shipping.address'),
            Arr::get($data, 'shipping.city'), Arr::get($data, 'shipping.postcode'),
        ])->filter()->implode(', ');

        foreach (['pay_before', 'placed_at'] as $field) {
            if (! empty($data[$field])) {
                try {
                    $vars[$field.'_formatted'] = Carbon::parse($data[$field])->setTimezone($tz)->format('j M Y, g:i A');
                } catch (\Throwable) {
                    $vars[$field.'_formatted'] = (string) $data[$field];
                }
            }
        }

        return $vars;
    }
}
