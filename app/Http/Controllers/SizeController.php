<?php

namespace App\Http\Controllers;

use App\Models\Size;
use App\Models\Category;
use App\Models\PriceLog;
use App\Http\Requests\StoreSizeRequest;
use App\Http\Requests\UpdateSizeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\QueryException;

class SizeController extends Controller
{
    public function index()
    {
        $kategori = Category::orderBy('urutan')->get();
        $ukuran = Size::with('category')->orderBy('category_id')->orderBy('urutan')->get();
        return view('admin.ukuran.index', compact('kategori', 'ukuran'));
    }

    public function create()
    {
        $kategori = Category::orderBy('urutan')->get();
        return view('admin.ukuran.create', compact('kategori'));
    }

    public function store(StoreSizeRequest $request): RedirectResponse
    {
        Size::create($request->validated());
        return redirect()->route('admin.ukuran.index')->with('pesan', 'Ukuran ditambahkan');
    }

    public function edit(Size $size)
    {
        $kategori = Category::orderBy('urutan')->get();
        return view('admin.ukuran.edit', compact('size', 'kategori'));
    }

    public function update(UpdateSizeRequest $request, Size $size): RedirectResponse
    {
        $size->update($request->validated());
        return redirect()->route('admin.ukuran.index')->with('pesan', 'Ukuran diubah');
    }

    public function destroy(Size $size): RedirectResponse
    {
        try {
            $size->delete();
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->withErrors(['server' => 'Ukuran ini pernah terjual dan tidak bisa dihapus. Nonaktifkan saja lewat Ubah.']);
            }
            throw $e;
        }
        return redirect()->route('admin.ukuran.index')->with('pesan', 'Ukuran dihapus');
    }

    public function riwayat(Size $size)
    {
        $logs = $size->priceLogs()->latest('diubah_pada')->get();
        return view('admin.ukuran.riwayat', compact('size', 'logs'));
    }
}
