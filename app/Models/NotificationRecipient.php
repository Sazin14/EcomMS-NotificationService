<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationRecipient extends Model
{
    protected $fillable = [
        'identity_type', 'identity_id', 'name', 'email', 'phone', 'locale',
        'is_active', 'marketing_consent_at', 'source_updated_at', 'synced_at',
        'external_id',
        'recipient_type',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'marketing_consent_at' => 'datetime',
            'source_updated_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function pushDevices(): HasMany
    {
        return $this->hasMany(PushDevice::class);
    }

    public function campaignRecipients(): HasMany
    {
        return $this->hasMany(NotificationCampaignRecipient::class);
    }

    public function campaignsCreated(): HasMany
    {
        return $this->hasMany(NotificationCampaign::class, 'created_by_recipient_id');
    }

    public function campaignsApproved(): HasMany
    {
        return $this->hasMany(NotificationCampaign::class, 'approved_by_recipient_id');
    }

    public function scopeForIdentity($query, string $type, int|string $id)
    {
        return $query->where('identity_type', $type)->where('identity_id', $id);
    }

}
