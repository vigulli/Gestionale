<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'service_category_id', 'name', 'description', 'type',
        'price_min', 'price_max', 'price_default', 'vat_rate',
        'active', 'sort_order',
    ];

    protected $casts = [
        'price_min'     => 'decimal:2',
        'price_max'     => 'decimal:2',
        'price_default' => 'decimal:2',
        'active'        => 'boolean',
    ];

    public function category() { return $this->belongsTo(ServiceCategory::class, 'service_category_id'); }
}
