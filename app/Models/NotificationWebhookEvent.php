<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationWebhookEvent extends Model
{
    protected $fillable = [
        'provider',
        'provider_event_id',
        'event_type',
        'payload',
        'received_at',
        'processed_at',
        'processing_error'];
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime'
        ];
    }
}
