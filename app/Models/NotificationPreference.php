<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = ['customer_id', 'channel', 'category', 'enabled'];

    protected $casts = ['customer_id' => 'integer', 'enabled' => 'boolean'];
}
