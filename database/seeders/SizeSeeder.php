<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SizeSeeder extends Seeder
{
    public function run(): void
    {
        $anak = DB::table('categories')->where('kode', 'anak')->first()->id;
        $dewasa = DB::table('categories')->where('kode', 'dewasa')->first()->id;
        $luar = DB::table('categories')->where('kode', 'luar')->first()->id;

        $ukuran = [
            ['cat' => $anak, 'nama' => 'No 2', 'harga' => 36500, 'urutan' => 1],
            ['cat' => $anak, 'nama' => 'No 4', 'harga' => 39000, 'urutan' => 2],
            ['cat' => $anak, 'nama' => 'No 6', 'harga' => 39000, 'urutan' => 3],
            ['cat' => $anak, 'nama' => 'No 8', 'harga' => 41500, 'urutan' => 4],
            ['cat' => $anak, 'nama' => 'No 10', 'harga' => 44000, 'urutan' => 5],
            ['cat' => $anak, 'nama' => 'No 12', 'harga' => 44000, 'urutan' => 6],
            ['cat' => $anak, 'nama' => 'No 14', 'harga' => 46500, 'urutan' => 7],
            ['cat' => $dewasa, 'nama' => 'M', 'harga' => 51000, 'urutan' => 1],
            ['cat' => $dewasa, 'nama' => 'L', 'harga' => 54000, 'urutan' => 2],
            ['cat' => $dewasa, 'nama' => 'XL', 'harga' => 57000, 'urutan' => 3],
            ['cat' => $dewasa, 'nama' => 'XXL', 'harga' => 54000, 'urutan' => 4],
            ['cat' => $luar, 'nama' => 'M', 'harga' => 60000, 'urutan' => 1],
            ['cat' => $luar, 'nama' => 'L', 'harga' => 60000, 'urutan' => 2],
            ['cat' => $luar, 'nama' => 'XL', 'harga' => 60000, 'urutan' => 3],
        ];

        foreach ($ukuran as $u) {
            DB::table('sizes')->updateOrInsert(
                ['category_id' => $u['cat'], 'nama' => $u['nama']],
                ['harga' => $u['harga'], 'urutan' => $u['urutan'], 'is_active' => true]
            );
        }
    }
}
