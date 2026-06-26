<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_number', 'supplier_name', 'supplier_email', 'supplier_phone',
        'status', 'payment_status', 'payment_method', 'subtotal', 'vat_amount',
        'total', 'paid_amount', 'invoice_ref', 'receipt_path', 'notes',
        'user_id', 'ordered_at', 'received_at', 'due_at',
    ];

    protected $casts = [
        'subtotal'    => 'decimal:2',
        'vat_amount'  => 'decimal:2',
        'total'       => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'ordered_at'  => 'date',
        'received_at' => 'date',
        'due_at'      => 'date',
    ];

    public static function statuses(): array
    {
        return [
            'ordered'   => ['label' => 'Ordinato',   'color' => 'blue'],
            'partial'   => ['label' => 'Parz. ricevuto', 'color' => 'yellow'],
            'received'  => ['label' => 'Ricevuto',   'color' => 'green'],
            'cancelled' => ['label' => 'Annullato',  'color' => 'red'],
        ];
    }

    public static function paymentStatuses(): array
    {
        return [
            'unpaid'  => ['label' => 'Non pagato', 'color' => 'red'],
            'partial' => ['label' => 'Parz. pagato', 'color' => 'yellow'],
            'paid'    => ['label' => 'Pagato',     'color' => 'green'],
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $purchase) {
            if (! $purchase->purchase_number) {
                $last = static::withTrashed()->max('id') ?? 0;
                $purchase->purchase_number = 'ACQ-' . str_pad($last + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function items() { return $this->hasMany(PurchaseItem::class); }
    public function user()  { return $this->belongsTo(User::class); }

    public function recalculate(): void
    {
        $subtotal = $this->items->sum(fn($i) => $i->line_total);
        $vat      = $this->items->sum(fn($i) => $i->line_total * $i->vat_rate / 100);
        $this->update(['subtotal' => $subtotal, 'vat_amount' => $vat, 'total' => $subtotal + $vat]);
    }
}
