<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $fillable = [
        'customer_id', 'channel', 'recipient', 'message',
        'status', 'provider', 'provider_response',
        'notifiable_type', 'notifiable_id', 'sent_at',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'sent_at'           => 'datetime',
    ];

    public function notifiable() { return $this->morphTo(); }
    public function customer()   { return $this->belongsTo(Customer::class); }
}
