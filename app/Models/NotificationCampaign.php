<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationCampaign extends Model
{

    protected $fillable = ['name',
        'channel',
        'subject',
        'body_html',
        'body_text',
        'audience',
        'status',
        'created_by_recipient_id',
        'approved_by_recipient_id',
        'approved_at',
        'scheduled_at',
        'started_at',
        'completed_at',
        'content_snapshot',
        'cancel_requested_at'];



    protected function casts(): array
    { return ['audience' => 'array',
        'content_snapshot' => 'array',
        'approved_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancel_requested_at' => 'datetime'];
    }
    public function recipients(): HasMany
    { return $this->hasMany(NotificationCampaignRecipient::class, 'campaign_id'); }
    public function creator(): BelongsTo
    { return $this->belongsTo(NotificationRecipient::class, 'created_by_recipient_id'); }
    public function approver(): BelongsTo
    { return $this->belongsTo(NotificationRecipient::class, 'approved_by_recipient_id'); }
    public function scopeDue($query)
    { return $query->where('status', 'scheduled')->whereNotNull('scheduled_at')->where('scheduled_at', '<=', now()); }
}
