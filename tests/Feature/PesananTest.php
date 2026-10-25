<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Size;
use App\Models\Order;
use Tests\TestCase;

class PesananTest extends TestCase
{
    public function test_simpan_pesanan_valid()
    {
        $size = Size::first();
        if (!$size) $this->markTestSkipped('No sizes in test database');

        $response = $this->postJson('/pesanan', [
            'nama_pembeli' => 'Uji',
            'items' => [['size_id' => $size->id, 'qty' => 2]],
        ]);

        $response->assertStatus(200)
                 ->assertJson(['ok' => true])
                 ->assertJsonStructure(['kode', 'total']);

        $this->assertDatabaseHas('orders', ['kode_pesanan' => $response->json('kode')]);
    }

    public function test_simpan_pesanan_rollback_invalid_size()
    {
        $response = $this->postJson('/pesanan', [
            'nama_pembeli' => 'Gagal',
            'items' => [['size_id' => 999999, 'qty' => 1]],
        ]);

        $response->assertStatus(422);
        $this->assertTrue($response->json('errors') !== null);
    }

    public function test_kasir_index_loads()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertViewHas('kategori');
    }

    public function test_pesanan_index_loads()
    {
        $response = $this->get('/pesanan');
        $response->assertStatus(200);
    }
}
