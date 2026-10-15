<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationDelivery extends Model
{
    protected $fillable = [
        'notification_id',
        'campaign_recipient_id',
        'channel',
        'provider',
        'provider_message_id',
        'destination',
        'status',
        'attempt_count',
        'next_attempt_at',
        'sent_at',
        'delivered_at',
        'failed_at',
        'last_error',
        'provider_response'];

    protected $casts = [
        'next_attempt_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'last_error' => 'datetime',
        'provider_response' => 'array',
    ];

    public function notification(): BelongsTo { return $this->belongsTo(Notification::class); }
    public function campaignRecipient(): BelongsTo { return $this->belongsTo(NotificationCampaignRecipient::class, 'campaign_recipient_id'); }

}
