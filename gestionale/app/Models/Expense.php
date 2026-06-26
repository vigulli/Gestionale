<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'expense_category_id', 'description', 'supplier', 'amount', 'vat_amount',
        'vat_rate', 'payment_method', 'reference', 'receipt_path', 'is_recurring',
        'recurring_period', 'notes', 'user_id', 'expense_date',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'vat_amount'   => 'decimal:2',
        'vat_rate'     => 'decimal:2',
        'is_recurring' => 'boolean',
        'expense_date' => 'date',
    ];

    public function category() { return $this->belongsTo(ExpenseCategory::class, 'expense_category_id'); }
    public function user()     { return $this->belongsTo(User::class); }
}
