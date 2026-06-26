<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone', 'company',
        'address', 'city', 'zip', 'country', 'tax_number',
        'category', 'notes', 'loyalty_points',
    ];

    protected $casts = ['loyalty_points' => 'decimal:2'];

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function repairs()           { return $this->hasMany(Repair::class); }
    public function sales()             { return $this->hasMany(Sale::class); }
    public function quotes()            { return $this->hasMany(Sale::class)->where('type', 'quote'); }
    public function notificationLogs()  { return $this->hasMany(NotificationLog::class); }

    // Totale fatturato (solo vendite pagate, non preventivi)
    public function getTotalInvoicedAttribute(): float
    {
        return $this->sales()->where('type', 'sale')->where('status', 'paid')->sum('total');
    }

    // Conteggi per la scheda cliente
    public function getStatsAttribute(): array
    {
        return [
            'repairs_count'    => $this->repairs()->count(),
            'repairs_open'     => $this->repairs()->whereNotIn('status', ['delivered', 'cancelled'])->count(),
            'invoices_count'   => $this->sales()->where('type', 'sale')->count(),
            'invoices_paid'    => $this->sales()->where('type', 'sale')->where('status', 'paid')->count(),
            'total_invoiced'   => $this->sales()->where('type', 'sale')->where('status', 'paid')->sum('total'),
            'quotes_count'     => $this->sales()->where('type', 'quote')->count(),
            'quotes_accepted'  => $this->sales()->where('type', 'quote')->where('quote_status', 'accepted')->count(),
        ];
    }
}
