<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    protected $fillable = [
        'type', 'description', 'expense_category_id', 'amount',
        'payment_method', 'reference', 'notes', 'user_id', 'movement_date',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'movement_date' => 'date',
    ];

    public function category() { return $this->belongsTo(ExpenseCategory::class, 'expense_category_id'); }
    public function user()     { return $this->belongsTo(User::class); }
}
