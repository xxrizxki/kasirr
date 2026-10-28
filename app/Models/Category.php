<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['kode', 'nama', 'urutan'];
    
    public function sizes() { return $this->hasMany(Size::class)->orderBy('urutan'); }
}
