<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            ['kode' => 'anak', 'nama' => 'Jubah anak', 'urutan' => 1],
            ['kode' => 'dewasa', 'nama' => 'Jubah dewasa', 'urutan' => 2],
            ['kode' => 'luar', 'nama' => 'Jubah luar', 'urutan' => 3],
        ];

        foreach ($kategori as $k) {
            DB::table('categories')->insertOrIgnore(
                array_merge($k, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
