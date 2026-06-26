<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairItem extends Model
{
    protected $fillable = [
        'repair_id', 'service_id', 'product_id',
        'description', 'qty', 'unit_price', 'vat_rate',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
    ];

    public function repair()  { return $this->belongsTo(Repair::class); }
    public function service() { return $this->belongsTo(Service::class); }
    public function product() { return $this->belongsTo(Product::class); }

    public function getLineTotalAttribute(): float
    {
        return round($this->qty * $this->unit_price, 2);
    }
}
