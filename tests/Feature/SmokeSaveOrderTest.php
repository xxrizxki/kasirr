<?php

namespace Tests\Feature;

use App\Models\Size;
use App\Models\Order;
use App\Models\PriceLog;
use Tests\TestCase;

class SmokeSaveOrderTest extends TestCase
{
    public function test_save_order_and_verify_total()
    {
        $size1 = Size::first(); // No 2, anak, 36500
        $size2 = Size::skip(7)->first(); // M, dewasa, 51000

        $response = $this->postJson('/pesanan', [
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

        // Manual calc: (2 * 36500) + (1 * 51000) = 73000 + 51000 = 124000
        $expected_total = 124000;
        
        $this->assertEquals($expected_total, $total_returned, "Total tidak sesuai: expected $expected_total, got $total_returned");

        // Verify di database
        $order = Order::where('kode_pesanan', $kode)->first();
        $this->assertNotNull($order, "Order tidak ditemukan di database");
        $this->assertEquals('Budi Santoso', $order->nama_pembeli);
        $this->assertEquals($expected_total, $order->total_harga);
        $this->assertEquals(3, $order->total_item); // 2 + 1
        $this->assertEquals('selesai', $order->status);
        $this->assertNotNull($order->created_at);

        return $order;
    }

    public function test_cancel_order()
    {
        $order = $this->test_save_order_and_verify_total();

        $response = $this->post(route('pesanan.batal', $order));
        
        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals('batal', $order->status);
        $this->assertNotNull($order->dibatalkan_pada);
    }

    public function test_price_change_logs_and_trigger()
    {
        $size = Size::first();
        $old_price = $size->harga;
        
        // Ubah harga via DB (simulate manual update)
        $size->update(['harga' => 37000]);

        // Verifikasi log tercatat (trigger harus mencatat)
        $log = PriceLog::where('size_id', $size->id)
                      ->where('harga_lama', $old_price)
                      ->where('harga_baru', 37000)
                      ->first();

        $this->assertNotNull($log, "Price log tidak tercatat oleh trigger");
        
        // Reset
        $size->update(['harga' => $old_price]);
    }

    public function test_laporan_shows_completed_orders()
    {
        // Save 2 orders
        $this->test_save_order_and_verify_total();
        $this->test_save_order_and_verify_total();

        $response = $this->get('/laporan');
        $response->assertStatus(200);
        $response->assertViewHas('rekap');
    }
}
