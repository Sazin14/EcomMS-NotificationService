<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushDevice extends Model
{
    protected $fillable = ['notification_recipient_id', 'provider', 'device_token', 'device_token_hash', 'platform', 'last_used_at', 'revoked_at'];
    protected function casts(): array { return ['last_used_at' => 'datetime', 'revoked_at' => 'datetime']; }
    public function recipient(): BelongsTo { return $this->belongsTo(NotificationRecipient::class, 'notification_recipient_id'); }
    public function scopeActive($query) { return $query->whereNull('revoked_at'); }
}
