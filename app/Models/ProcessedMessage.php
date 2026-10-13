<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessedMessage extends Model
{
    protected $fillable = ['source_service', 'event_id', 'event_type', 'status', 'payload', 'received_at', 'processed_at', 'last_error'];
    protected function casts(): array { return ['payload' => 'array', 'received_at' => 'datetime', 'processed_at' => 'datetime']; }
    public function notifications(): HasMany { return $this->hasMany(Notification::class); }
}
