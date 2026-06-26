<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Repair extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_number', 'customer_id', 'device_brand', 'device_model',
        'device_serial', 'device_password', 'problem_description',
        'technician_notes', 'status', 'priority', 'assigned_to',
        'estimated_cost', 'final_cost', 'vat_rate', 'customer_notified',
        'tracking_token', 'received_at', 'deadline_at', 'completed_at',
    ];

    protected $casts = [
        'customer_notified' => 'boolean',
        'estimated_cost' => 'decimal:2',
        'final_cost' => 'decimal:2',
        'received_at' => 'datetime',
        'deadline_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            'received'       => ['label' => 'Ricevuto',           'color' => 'blue'],
            'diagnosed'      => ['label' => 'Diagnosticato',      'color' => 'purple'],
            'in_progress'    => ['label' => 'In lavorazione',     'color' => 'yellow'],
            'waiting_parts'  => ['label' => 'In attesa ricambi',  'color' => 'orange'],
            'ready'          => ['label' => 'Pronto',             'color' => 'green'],
            'delivered'      => ['label' => 'Consegnato',         'color' => 'gray'],
            'cancelled'      => ['label' => 'Annullato',          'color' => 'red'],
        ];
    }

    public static function priorities(): array
    {
        return [
            'low'    => ['label' => 'Bassa',   'color' => 'gray'],
            'normal' => ['label' => 'Normale', 'color' => 'blue'],
            'high'   => ['label' => 'Alta',    'color' => 'orange'],
            'urgent' => ['label' => 'Urgente', 'color' => 'red'],
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Repair $repair) {
            if (empty($repair->ticket_number)) {
                $repair->ticket_number = 'REP-' . strtoupper(Str::random(6));
            }
            if (empty($repair->tracking_token)) {
                $repair->tracking_token = Str::uuid()->toString();
            }
        });

        static::updated(function (Repair $repair) {
            if ($repair->wasChanged('status')) {
                RepairStatusHistory::create([
                    'repair_id'  => $repair->id,
                    'status'     => $repair->status,
                    'changed_at' => now(),
                    'user_id'    => auth()->id(),
                ]);
            }
        });
    }

    public function customer()      { return $this->belongsTo(Customer::class); }
    public function assignedTo()    { return $this->belongsTo(User::class, 'assigned_to'); }
    public function items()         { return $this->hasMany(RepairItem::class); }
    public function statusHistory() { return $this->hasMany(RepairStatusHistory::class)->orderBy('changed_at'); }

    public function getStatusLabelAttribute(): string
    {
        return self::statuses()[$this->status]['label'] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::statuses()[$this->status]['color'] ?? 'gray';
    }

    public function getTrackingUrlAttribute(): string
    {
        return url('/track/' . $this->tracking_token);
    }
}
