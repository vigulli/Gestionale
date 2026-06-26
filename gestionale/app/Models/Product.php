<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'sku', 'barcode', 'type', 'description',
        'purchase_price', 'sell_price', 'vat_rate',
        'stock_qty', 'stock_alert', 'supplier',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sell_price'     => 'decimal:2',
    ];

    public function purchaseItems() { return $this->hasMany(PurchaseItem::class); }
    public function repairItems()   { return $this->hasMany(RepairItem::class); }
    public function saleItems()     { return $this->hasMany(SaleItem::class); }
}
