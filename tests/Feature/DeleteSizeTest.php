<?php

namespace Tests\Feature;

use App\Models\Size;
use App\Models\User;
use Tests\TestCase;

class DeleteSizeTest extends TestCase
{
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_delete_size_used_in_order_rejected()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $size = Size::whereHas('category', fn($q) => $q->where('kode', 'anak'))
                    ->where('nama', 'No 2')->first();
        
        // Buat pesanan dengan ukuran ini
        $this->actingAs($user)->postJson('/pesanan', [
            'nama_pembeli' => 'Uji',
            'items' => [['size_id' => $size->id, 'qty' => 1]],
        ]);
        
        // Coba hapus
        $response = $this->actingAs($this->admin)->delete("/admin/ukuran/$size->id");
        
        $response->assertSessionHasErrors('server');
        $response->assertSessionHasNoErrors('server', 'Nonaktifkan saja');
        $this->assertDatabaseHas('sizes', ['id' => $size->id]);
    }

    public function test_delete_size_unused_succeeds()
    {
        $size = Size::create([
            'category_id' => Size::first()->category_id,
            'nama' => 'Test Size Unused',
            'harga' => 50000,
            'urutan' => 999,
            'is_active' => true,
        ]);
        
        $response = $this->actingAs($this->admin)->delete("/admin/ukuran/$size->id");
        
        $response->assertRedirect();
        $this->assertDatabaseMissing('sizes', ['id' => $size->id]);
    }
}
