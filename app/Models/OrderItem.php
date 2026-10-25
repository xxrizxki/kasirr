<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    public $timestamps = false;
    public function order() { return $this->belongsTo(Order::class); }
    public function size() { return $this->belongsTo(Size::class); }
}
