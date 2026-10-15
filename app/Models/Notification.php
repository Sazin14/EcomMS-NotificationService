<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Notification extends Model
{
     protected $fillable=[
         'processed_message_id',
         'notification_recipient_id',
         'template_id',
         'type',
         'title',
         'body',
         'link_url',
         'data',
         'priority',
         'read_at'];

    protected function casts(): array {
        return ['data' => 'array',
            'read_at' => 'datetime']; }
    public function recipient(): BelongsTo {
        return $this->belongsTo(NotificationRecipient::class, 'notification_recipient_id'); }
    public function processedMessage(): BelongsTo {
        return $this->belongsTo(ProcessedMessage::class); }
    public function template(): BelongsTo {
        return $this->belongsTo(NotificationTemplate::class, 'template_id'); }
    public function deliveries(): HasMany {
        return $this->hasMany(NotificationDelivery::class); }
    public function scopeUnread($query) {
        return $query->whereNull('read_at'); }
}
