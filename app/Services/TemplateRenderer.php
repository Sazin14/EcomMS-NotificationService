<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use Illuminate\Support\Arr;

/**
 * Plain-text templates with {{ placeholders }}, stored in the notification_templates table so staff can edit them.
 * There is deliberately no Blade / code execution in a stored template: a placeholder is only ever replaced by text.
 *   {{ order_number }}   {{ customer.name }}   {{ items_text }}
 * An unknown placeholder becomes an empty string.
 */
class TemplateRenderer
{
    public function render(string $template, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*([A-Za-z0-9_.]+)\s*\}\}/', function (array $m) use ($vars) {
            $value = Arr::get($vars, $m[1]);

            return is_scalar($value) ? (string) $value : '';
        }, $template);
    }

    /**
     * @return array{subject:string, body:string}|null  null when no active template exists (not even in English)
     */
    public function build(string $key, string $channel, string $locale, array $vars): ?array
    {
        $template = NotificationTemplate::query()
            ->where('key', $key)
            ->where('channel', $channel)
            ->where('is_active', true)
            ->whereIn('locale', array_unique([$locale, 'en']))
            ->orderByRaw("case when locale = ? then 0 else 1 end", [$locale])   // the customer's language first, English as fallback
            ->first();

        if (! $template) {
            return null;
        }

        return [
            'subject' => trim($this->render((string) $template->subject, $vars)),
            'body' => trim($this->render($template->body, $vars)),
        ];
    }
}
