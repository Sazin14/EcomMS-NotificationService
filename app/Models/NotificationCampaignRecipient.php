<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationCampaignRecipient extends Model
{
    protected $fillable = [
        'campaign_id',
        'notification_recipient_id',
        'destination',
        'personalization_data',
        'status',
        'skip_reason',
        'queued_at',
        'completed_at'];


    protected function casts(): array {
        return ['personalization_data' => 'array',
            'queued_at' => 'datetime',
            'completed_at' => 'datetime']; }
    public function campaign(): BelongsTo { return $this->belongsTo(NotificationCampaign::class, 'campaign_id'); }
    public function recipient(): BelongsTo { return $this->belongsTo(NotificationRecipient::class, 'notification_recipient_id'); }
    public function deliveries(): HasMany { return $this->hasMany(NotificationDelivery::class, 'campaign_recipient_id'); }
}
