<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDelivery extends Model
{
    public const PENDING = 'pending';
    public const SENT = 'sent';
    public const FAILED = 'failed';
    public const SKIPPED = 'skipped';

    protected $fillable = [
        'notification_id', 'channel', 'address', 'subject', 'body', 'status', 'attempts',
        'last_error', 'provider_message_id', 'sent_at',
    ];

    protected $casts = ['attempts' => 'integer', 'sent_at' => 'datetime'];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}
