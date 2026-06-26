<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id', 'product_id', 'service_id',
        'description', 'qty', 'unit_price', 'discount_pct', 'vat_rate', 'line_total',
    ];

    protected $casts = [
        'qty'          => 'decimal:2',
        'unit_price'   => 'decimal:2',
        'discount_pct' => 'decimal:2',
        'vat_rate'     => 'decimal:2',
        'line_total'   => 'decimal:2',
    ];

    public function sale()    { return $this->belongsTo(Sale::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function service() { return $this->belongsTo(Service::class); }
}
