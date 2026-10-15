<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'notification_recipient_id',
        'channel',
        'category',
        'is_enabled',
        'consented_at',
        'consent_source'];
    protected function casts(): array
    { return [
        'is_enabled' => 'boolean',
        'consented_at' => 'datetime'];
    }
    public function recipient(): BelongsTo { return $this->belongsTo(NotificationRecipient::class, 'notification_recipient_id'); }
}
