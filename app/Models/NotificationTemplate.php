<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class NotificationTemplate extends Model
{
    //
    protected $fillable = ['key', 'channel', 'locale', 'category', 'subject', 'body', 'version', 'is_active'];

    protected function casts(): array { return ['is_active' => 'boolean', 'version' => 'integer']; }

    public function notifications(): HasMany { return $this->hasMany(Notification::class, 'template_id'); }

    public function scopeActive($query) { return $query->where('is_active', true); }

    public function scopeForTemplate($query, string $key, string $channel, string $locale = 'en')
    {
        return $query->where('key', $key)->where('channel', $channel)->where('locale', $locale)->where('is_active', true);
    }
}
