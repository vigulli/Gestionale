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

    public function repairs()     { return $this->hasMany(Repair::class); }
    public function sales()       { return $this->hasMany(Sale::class); }
}
