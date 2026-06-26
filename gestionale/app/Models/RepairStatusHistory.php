<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairStatusHistory extends Model
{
    protected $fillable = ['repair_id', 'status', 'note', 'user_id', 'changed_at'];
    protected $casts = ['changed_at' => 'datetime'];

    public function repair() { return $this->belongsTo(Repair::class); }
    public function user()   { return $this->belongsTo(User::class); }
}
