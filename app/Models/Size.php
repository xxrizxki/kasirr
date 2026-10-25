<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    protected $fillable = ['harga', 'is_active', 'urutan'];
    
    public function category() { return $this->belongsTo(Category::class); }
    public function orderItems() { return $this->hasMany(OrderItem::class); }
    public function priceLogs() { return $this->hasMany(PriceLog::class); }
    public function scopeAktif($q) { return $q->where('is_active', true); }
    
    public function getHargaRupiahAttribute() {
        return 'Rp ' . number_format($this->harga, 0, ',', '.');
    }
}
