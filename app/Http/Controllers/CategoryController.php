<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    public function index()
    {
        $kategori = Category::with('sizes')->orderBy('urutan')->get();
        return view('admin.kategori.index', compact('kategori'));
    }

    public function create()
    {
        return view('admin.kategori.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());
        return redirect()->route('admin.kategori.index')->with('pesan', 'Kategori ditambahkan');
    }

    public function edit(Category $category)
    {
        return view('admin.kategori.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());
        return redirect()->route('admin.kategori.index')->with('pesan', 'Kategori diubah');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->sizes()->exists()) {
            return back()->withErrors(['server' => 'Hapus atau pindahkan ukurannya dulu.']);
        }
        $category->delete();
        return redirect()->route('admin.kategori.index')->with('pesan', 'Kategori dihapus');
    }
}
