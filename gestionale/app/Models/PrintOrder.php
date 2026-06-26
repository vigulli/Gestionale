<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PrintOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number', 'customer_id', 'status', 'assigned_to',
        'garment_type', 'garment_color', 'garment_size', 'quantity',
        'print_description', 'print_file_path', 'print_position',
        'print_size_cm', 'unit_price', 'total_price', 'vat_rate',
        'notes', 'tracking_token', 'deadline_at',
    ];

    protected $casts = [
        'unit_price'  => 'decimal:2',
        'total_price' => 'decimal:2',
        'deadline_at' => 'datetime',
    ];

    // Tipi capo predefiniti
    public static function garmentTypes(): array
    {
        return [
            'tshirt'      => 'T-Shirt',
            'polo'        => 'Polo',
            'felpa'       => 'Felpa',
            'hoodie'      => 'Hoodie / Felpa con cappuccio',
            'gilet'       => 'Gilet',
            'giacca'      => 'Giacca',
            'pantaloni'   => 'Pantaloni da lavoro',
            'tuta'        => 'Tuta intera',
            'grembiule'   => 'Grembiule',
            'cappello'    => 'Cappello / Berretto',
            'borsa'       => 'Borsa / Tote bag',
            'altro'       => 'Altro',
        ];
    }

    public static function positions(): array
    {
        return ['Fronte', 'Retro', 'Manica sinistra', 'Manica destra', 'Taschino', 'Colletto', 'Personalizzato'];
    }

    public static function sizes(): array
    {
        return ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL', 'Taglia unica'];
    }

    public static function statuses(): array
    {
        return [
            'pending'   => ['label' => 'In attesa',      'color' => 'gray'],
            'confirmed' => ['label' => 'Confermato',     'color' => 'blue'],
            'printing'  => ['label' => 'In stampa',      'color' => 'yellow'],
            'ready'     => ['label' => 'Pronto',         'color' => 'green'],
            'delivered' => ['label' => 'Consegnato',     'color' => 'purple'],
            'cancelled' => ['label' => 'Annullato',      'color' => 'red'],
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PrintOrder $order) {
            if (empty($order->order_number)) {
                $order->order_number = 'DTF-' . strtoupper(Str::random(6));
            }
            if (empty($order->tracking_token)) {
                $order->tracking_token = Str::uuid()->toString();
            }
        });

        static::saving(function (PrintOrder $order) {
            $order->total_price = $order->unit_price * $order->quantity;
        });
    }

    public function customer()   { return $this->belongsTo(Customer::class); }
    public function assignedTo() { return $this->belongsTo(User::class, 'assigned_to'); }

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
        return url('/track-order/' . $this->tracking_token);
    }
}
