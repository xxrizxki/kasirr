<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Size;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class HargaService
{
    /** @param array<int,array{harga:int,is_active:bool}> $perubahan [size_id => ['harga' => ..., 'is_active' => ...]] */
    public function ubahBanyak(array $perubahan): int
    {
        DB::beginTransaction();
        try {
            $berubah = 0;
            foreach ($perubahan as $sizeId => $data) {
                if ($data['harga'] < 0) {
                    throw new InvalidArgumentException("Harga ukuran #$sizeId tidak boleh negatif");
                }
                $berubah += Size::whereKey($sizeId)->update([
                    'harga'     => $data['harga'],
                    'is_active' => (bool)$data['is_active'],
                ]);
            }
            DB::commit();
            return $berubah;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function ubahMassal(Category $kategori, int $selisih): void
    {
        DB::statement('CALL sp_ubah_harga_massal(?, ?)', [$kategori->id, $selisih]);
    }
}
}
