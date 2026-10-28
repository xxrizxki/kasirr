<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PriceLog;
use App\Services\HargaService;
use App\Support\PesanError;
use App\Http\Requests\UbahHargaRequest;

class HargaController extends Controller
{
    public function __construct(protected HargaService $service) {}

    public function edit()
    {
        $kategori = Category::with('sizes')->orderBy('urutan')->get();
        $logs = PriceLog::with('size.category')->latest('diubah_pada')->limit(30)->get();
        
        return view('admin.harga', compact('kategori', 'logs'));
    }

    public function update(UbahHargaRequest $request)
    {
        $perubahan = [];
        foreach ($request->input('harga', []) as $sizeId => $harga) {
            $perubahan[$sizeId] = [
                'harga' => (int) $harga,
                'is_active' => $request->boolean("aktif.$sizeId"),
            ];
        }

        try {
            $n = $this->service->ubahBanyak($perubahan);
            return back()->with('pesan', "$n harga diubah");
        } catch (\Throwable $e) {
            return back()->withErrors(['server' => PesanError::dari($e)]);
        }
    }

    public function massal(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'selisih' => ['required', 'integer', 'not_in:0', 'between:-1000000,1000000'],
        ], [], [], ['server' => 'Validasi gagal']);

        try {
            $kategori = Category::findOrFail($request->category_id);
            $this->service->ubahMassal($kategori, (int) $request->selisih);
            return back()->with('pesan', 'Harga kategori diubah');
        } catch (\Throwable $e) {
            return back()->withErrors(['server' => PesanError::dari($e)]);
        }
    }
}
