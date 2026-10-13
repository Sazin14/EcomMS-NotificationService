<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSuppression extends Model
{
    protected $fillable = [
        'channel',
        'destination_normalized',
        'reason',
        'source',
        'suppressed_at',
        'metadata'];


    protected function casts(): array
    {
        return [
            'suppressed_at' => 'datetime',
            'metadata' => 'array'
        ];
    }

    public function scopeForDestination($query, string $channel, string $destination)
    {
        $normalized = $channel === 'email' ? mb_strtolower(trim($destination)) : preg_replace('/\s+/', '', trim($destination));
        return $query->where('channel', $channel)->where('destination_normalized', $normalized);
    }
}
