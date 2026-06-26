<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $fillable = ['name', 'color', 'type', 'sort_order'];

    public function expenses()      { return $this->hasMany(Expense::class); }
    public function cashMovements() { return $this->hasMany(CashMovement::class); }
}
