<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number', 'customer_id', 'type', 'status',
        'payment_method', 'payment_reference',
        'subtotal', 'vat_amount', 'total', 'paid_amount', 'discount_amount',
        'vat_mode', 'notes', 'pdf_path', 'qr_bill_generated',
        'quote_token', 'quote_status', 'quote_responded_at',
        'quote_response_ip', 'quote_rejection_reason',
        'source_type', 'source_id',
        'issued_at', 'due_at',
    ];

    protected $casts = [
        'subtotal'             => 'decimal:2',
        'vat_amount'           => 'decimal:2',
        'total'                => 'decimal:2',
        'paid_amount'          => 'decimal:2',
        'discount_amount'      => 'decimal:2',
        'qr_bill_generated'    => 'boolean',
        'issued_at'            => 'datetime',
        'due_at'               => 'datetime',
        'quote_responded_at'   => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Sale $sale) {
            if ($sale->type === 'quote' && empty($sale->quote_token)) {
                $sale->quote_token = Str::uuid()->toString();
            }
        });
    }

    public function customer() { return $this->belongsTo(Customer::class); }
    public function items()    { return $this->hasMany(SaleItem::class); }
    public function source()   { return $this->morphTo(); }

    public function recalculate(): void
    {
        $subtotal = $this->items->sum(fn($i) => $i->qty * $i->unit_price * (1 - $i->discount_pct / 100));
        $vat      = $this->items->sum(fn($i) => $i->qty * $i->unit_price * (1 - $i->discount_pct / 100) * ($i->vat_rate / 100));
        $this->update([
            'subtotal'   => $subtotal,
            'vat_amount' => $vat,
            'total'      => $subtotal + $vat,
        ]);
    }

    public function getQuoteUrlAttribute(): string
    {
        return url('/quote/' . $this->quote_token);
    }
}
