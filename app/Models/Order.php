<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $casts = ['status' => 'string'];
    public function items() { return $this->hasMany(OrderItem::class); }
    
    public function getTotalRupiahAttribute() {
        return 'Rp ' . number_format($this->total_harga, 0, ',', '.');
    }
    public function isSelesai(): bool { return $this->status === 'selesai'; }
}
