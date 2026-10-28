<?php

namespace Tests\Feature;

use App\Models\Size;
use App\Models\Order;
use App\Models\User;
use Tests\TestCase;

class RollbackTest extends TestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
    }

    public function test_rollback_with_inactive_size()
    {
        $sizeAnak = Size::whereHas('category', fn($q) => $q->where('kode', 'anak'))
                        ->where('nama', 'No 2')->first();
        $sizeDewasa = Size::whereHas('category', fn($q) => $q->where('kode', 'dewasa'))
                          ->where('nama', 'M')->first();
        
        // Set satu ukuran jadi nonaktif
        $sizeDewasa->update(['is_active' => false]);
        
        $initialCount = Order::count();
        
        // Kirim ke POST dengan satu ukuran valid dan satu nonaktif
        $response = $this->actingAs($this->user)->postJson('/pesanan', [
            'nama_pembeli' => 'Uji Rollback',
            'items' => [
                ['size_id' => $sizeAnak->id, 'qty' => 2],
                ['size_id' => $sizeDewasa->id, 'qty' => 1],
            ],
        ]);
        
        // Assert ditolak
        $response->assertStatus(422);
        $this->assertFalse($response->json('ok') ?? false);
        
        // Assert tidak ada pesanan baru (rollback)
        $this->assertEquals($initialCount, Order::count());
    }

    public function test_harga_satuan_immutable_after_size_price_change()
    {
        $size = Size::whereHas('category', fn($q) => $q->where('kode', 'anak'))
                    ->where('nama', 'No 2')->first();
        $originalHarga = $size->harga;
        
        $response = $this->actingAs($this->user)->postJson('/pesanan', [
            'nama_pembeli' => 'Uji',
            'items' => [['size_id' => $size->id, 'qty' => 1]],
        ]);
        
        $order = Order::where('kode_pesanan', $response->json('kode'))->first();
        $itemHargaSatuan = $order->items->first()->harga_satuan;
        
        // Ubah harga di sizes
        $size->update(['harga' => 50000]);
        
        // Assert harga_satuan tidak berubah
        $this->assertEquals($originalHarga, $itemHargaSatuan);
    }

    public function test_cancel_order_twice_fails()
    {
        $order = $this->buatPesanan('Uji Cancel Twice');
        
        // Batalkan pertama kali
        $response1 = $this->actingAs($this->user)->post(route('pesanan.batal', $order));
        $response1->assertRedirect();
        
        // Batalkan kedua kali
        $response2 = $this->actingAs($this->user)->post(route('pesanan.batal', $order));
        $response2->assertSessionHasErrors('server');
    }
}
