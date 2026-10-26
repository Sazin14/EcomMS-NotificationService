<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    public const CUSTOMER = 'customer';
    public const STAFF = 'staff';

    protected $fillable = [
        'dedupe_key', 'recipient_type', 'recipient_id', 'audience', 'event_type', 'in_app',
        'title', 'body', 'data', 'read_at',
    ];

    protected $hidden = ['dedupe_key'];

    protected $casts = [
        'recipient_id' => 'integer',
        'in_app' => 'boolean',
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(NotificationRead::class);
    }
}
