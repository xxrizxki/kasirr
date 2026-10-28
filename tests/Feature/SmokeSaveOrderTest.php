<?php

namespace Tests\Feature;

use App\Models\Size;
use App\Models\Order;
use App\Models\PriceLog;
use App\Models\User;
use Tests\TestCase;

class SmokeSaveOrderTest extends TestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
    }

    protected function buatPesanan($nama = 'Uji', $items = null): Order
    {
        if (!$items) {
            $size1 = Size::whereHas('category', fn($q) => $q->where('kode', 'anak'))
                         ->where('nama', 'No 2')->first();
            $size2 = Size::whereHas('category', fn($q) => $q->where('kode', 'dewasa'))
                         ->where('nama', 'M')->first();
            $items = [
                ['size_id' => $size1->id, 'qty' => 2],
                ['size_id' => $size2->id, 'qty' => 1],
            ];
        }

        $response = $this->actingAs($this->user)->postJson('/pesanan', [
            'nama_pembeli' => $nama,
            'items' => $items,
        ]);

        $this->assertTrue($response->json('ok'), 'Gagal membuat pesanan');
        return Order::where('kode_pesanan', $response->json('kode'))->first();
    }

    public function test_save_order_and_verify_total()
    {
        $size1 = Size::whereHas('category', fn($q) => $q->where('kode', 'anak'))
                     ->where('nama', 'No 2')->first();
        $size2 = Size::whereHas('category', fn($q) => $q->where('kode', 'dewasa'))
                     ->where('nama', 'M')->first();

        $response = $this->actingAs($this->user)->postJson('/pesanan', [
            'nama_pembeli' => 'Budi Santoso',
            'items' => [
                ['size_id' => $size1->id, 'qty' => 2],
                ['size_id' => $size2->id, 'qty' => 1],
            ],
        ]);

        $response->assertStatus(200)
                 ->assertJson(['ok' => true])
                 ->assertJsonStructure(['kode', 'total']);

        $kode = $response->json('kode');
        $total_returned = $response->json('total');

        // Harga SPEC: No 2 anak = 36500, M dewasa = 51000
        $expected_total = (2 * 36500) + (1 * 51000); // 124000
        
        $this->assertEquals($expected_total, $total_returned, "Total tidak sesuai: expected $expected_total, got $total_returned");

        // Verify di database
        $order = Order::where('kode_pesanan', $kode)->first();
        $this->assertNotNull($order, "Order tidak ditemukan di database");
        $this->assertEquals('Budi Santoso', $order->nama_pembeli);
        $this->assertEquals($expected_total, $order->total_harga);
        $this->assertEquals(3, $order->total_item);
        $this->assertEquals('selesai', $order->status);
        $this->assertNotNull($order->created_at);
    }

    public function test_cancel_order()
    {
        $order = $this->buatPesanan('Uji Batal');

        $response = $this->actingAs($this->user)->post(route('pesanan.batal', $order));
        
        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals('batal', $order->status);
        $this->assertNotNull($order->dibatalkan_pada);
    }

    public function test_price_change_logs_and_trigger()
    {
        $size = Size::first();
        $old_price = $size->harga;
        
        $size->update(['harga' => 37000]);
        $log = PriceLog::where('size_id', $size->id)
                      ->where('harga_lama', $old_price)
                      ->where('harga_baru', 37000)
                      ->first();

        $this->assertNotNull($log, "Price log tidak tercatat oleh trigger");
        $size->update(['harga' => $old_price]);
    }

    public function test_laporan_shows_completed_orders()
    {
        $this->buatPesanan('Pesanan 1');
        $this->buatPesanan('Pesanan 2');

        $response = $this->actingAs($this->user)->get('/laporan');
        $response->assertStatus(200);
        $response->assertViewHas('rekap');
    }
}
