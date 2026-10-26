<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Akun admin demo - idempotent via updateOrCreate
        User::updateOrCreate(
            ['email' => 'admin123@gmail.com'],
            [
                'name' => 'Admin Demo',
                'email' => 'admin123@gmail.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Dummy harga - berbeda dari data toko asli
        // Kategori anak: dummy mulai 20000
        $anak = DB::table('categories')->where('kode', 'anak')->first();
        if ($anak) {
            $dummy_anak = [
                ['nama' => 'No 2', 'harga' => 20000],
                ['nama' => 'No 4', 'harga' => 22000],
                ['nama' => 'No 6', 'harga' => 22000],
                ['nama' => 'No 8', 'harga' => 24000],
                ['nama' => 'No 10', 'harga' => 26000],
                ['nama' => 'No 12', 'harga' => 26000],
                ['nama' => 'No 14', 'harga' => 28000],
            ];
            foreach ($dummy_anak as $idx => $u) {
                DB::table('sizes')->where('category_id', $anak->id)
                    ->where('nama', $u['nama'])
                    ->update(['harga' => $u['harga']]);
            }
        }

        // Kategori dewasa: dummy mulai 30000
        $dewasa = DB::table('categories')->where('kode', 'dewasa')->first();
        if ($dewasa) {
            $dummy_dewasa = [
                ['nama' => 'M', 'harga' => 30000],
                ['nama' => 'L', 'harga' => 32000],
                ['nama' => 'XL', 'harga' => 35000],
                ['nama' => 'XXL', 'harga' => 32000],
            ];
            foreach ($dummy_dewasa as $u) {
                DB::table('sizes')->where('category_id', $dewasa->id)
                    ->where('nama', $u['nama'])
                    ->update(['harga' => $u['harga']]);
            }
        }

        // Kategori luar: dummy 35000
        $luar = DB::table('categories')->where('kode', 'luar')->first();
        if ($luar) {
            DB::table('sizes')->where('category_id', $luar->id)
                ->update(['harga' => 35000]);
        }
    }
}
