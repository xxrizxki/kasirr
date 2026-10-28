<?php

namespace Tests\Feature;

use App\Models\Size;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LaporanTimezoneTest extends TestCase
{
    public function test_app_timezone_is_jakarta()
    {
        $this->assertEquals('Asia/Jakarta', config('app.timezone'));
    }

    public function test_rekap_harian_shows_today_order()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $size = Size::first();
        
        // Buat pesanan
        $response = $this->actingAs($admin)->postJson('/pesanan', [
            'nama_pembeli' => 'Uji Zona',
            'items' => [['size_id' => $size->id, 'qty' => 1]],
        ]);
        
        $this->assertTrue($response->json('ok'));
        
        // Cek laporan
        $today = now()->format('Y-m-d');
        $rekap = DB::select('CALL sp_rekap_harian(?, ?)', [$today, $today]);
        
        $this->assertNotEmpty($rekap, "Pesanan hari ini tidak muncul di sp_rekap_harian tanggal $today");
        $this->assertEquals(1, $rekap[0]->jumlah_pesanan);
    }
}
