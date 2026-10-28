<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class DatabaseFunctionsTest extends TestCase
{
    public function test_fn_subtotal_calculation()
    {
        $result = DB::selectOne('SELECT fn_subtotal(3, 39000) as subtotal');
        $this->assertEquals(117000, $result->subtotal);
    }

    public function test_fn_total_pesanan_calculation()
    {
        $result = DB::selectOne('SELECT fn_total_pesanan(999999) as total');
        $this->assertEquals(0, $result->total);
    }

    public function test_sp_ubah_harga_massal_success()
    {
        $categoryId = DB::table('categories')->where('kode', 'anak')->value('id');
        DB::statement('CALL sp_ubah_harga_massal(?, ?)', [$categoryId, 1000]);
        
        $harga = DB::table('sizes')->where('category_id', $categoryId)->min('harga');
        $this->assertGreaterThan(36500, $harga);
    }

    public function test_sp_ubah_harga_massal_rollback_negative()
    {
        $categoryId = DB::table('categories')->where('kode', 'anak')->value('id');
        $originalHarga = DB::table('sizes')->where('category_id', $categoryId)->min('harga');
        
        $this->expectException(\Exception::class);
        DB::statement('CALL sp_ubah_harga_massal(?, ?)', [$categoryId, -999999]);
        
        $newHarga = DB::table('sizes')->where('category_id', $categoryId)->min('harga');
        $this->assertEquals($originalHarga, $newHarga);
    }
}
