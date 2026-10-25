<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceLog extends Model
{
    public $timestamps = false;
    public function size() { return $this->belongsTo(Size::class); }
}
