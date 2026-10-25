<?php

namespace App\Http\Controllers;

use App\Models\Category;

class KasirController extends Controller
{
    public function index()
    {
        $kategori = Category::with(['sizes' => fn ($q) => $q->aktif()])->orderBy('urutan')->get();

        return view('kasir', compact('kategori'));
    }
}
