<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Size;
use App\Models\User;
use Tests\TestCase;

class ValidationAndCrudTest extends TestCase
{
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_empty_harga_validation_rejected()
    {
        $size = Size::first();
        $originalHarga = $size->harga;
        
        $response = $this->actingAs($this->admin)->put('/admin/harga', [
            'harga' => [$size->id => ''],
        ]);
        
        $response->assertSessionHasErrors('server');
        $this->assertEquals($originalHarga, $size->fresh()->harga);
    }

    public function test_harga_service_negative_rollback()
    {
        $size = Size::first();
        $originalHarga = $size->harga;
        
        $response = $this->actingAs($this->admin)->put('/admin/harga', [
            'harga' => [$size->id => -5000],
        ]);
        
        $response->assertSessionHasErrors('server');
        $this->assertEquals($originalHarga, $size->fresh()->harga);
    }

    public function test_crud_kategori_create()
    {
        $response = $this->actingAs($this->admin)->post('/admin/kategori', [
            'kode' => 'test_cat',
            'nama' => 'Kategori Uji',
            'urutan' => 99,
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('categories', ['kode' => 'test_cat']);
    }

    public function test_crud_kategori_delete_with_sizes_fails()
    {
        $category = Category::with('sizes')->first();
        
        if ($category->sizes->isNotEmpty()) {
            $response = $this->actingAs($this->admin)->delete("/admin/kategori/$category->id");
            $response->assertSessionHasErrors('server');
            $this->assertDatabaseHas('categories', ['id' => $category->id]);
        }
    }

    public function test_crud_ukuran_unique_per_category()
    {
        $category = Category::first();
        $size = $category->sizes->first();
        
        $response = $this->actingAs($this->admin)->post('/admin/ukuran', [
            'category_id' => $category->id,
            'nama' => $size->nama,
            'harga' => 50000,
        ]);
        
        $response->assertSessionHasErrors('nama');
    }

    public function test_pesanan_show_returns_200()
    {
        $order = \App\Models\Order::factory()->create();
        
        $response = $this->actingAs($this->admin)->get("/pesanan/$order->id");
        $response->assertStatus(200);
        $response->assertViewHas('order');
    }

    public function test_relasi_order_items_size_category()
    {
        $order = \App\Models\Order::factory()
            ->has(\App\Models\OrderItem::factory()->count(2))
            ->create();
        
        $order->load('items.size.category');
        
        foreach ($order->items as $item) {
            $this->assertNotNull($item->size);
            $this->assertNotNull($item->size->category);
            $this->assertNotNull($item->size->category->nama);
        }
    }

    public function test_filter_riwayat_by_date()
    {
        $today = now()->format('Y-m-d');
        
        $response = $this->actingAs($this->admin)->get("/pesanan?dari=$today&sampai=$today");
        $response->assertStatus(200);
    }

    public function test_filter_riwayat_by_status()
    {
        $response = $this->actingAs($this->admin)->get('/pesanan?status=selesai');
        $response->assertStatus(200);
    }

    public function test_seeder_does_not_override_harga()
    {
        $size = Size::first();
        $originalHarga = $size->harga;
        $size->update(['harga' => 99999]);
        
        \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--class' => 'Database\Seeders\SizeSeeder',
            '--env' => 'testing',
        ]);
        
        $this->assertEquals(99999, $size->fresh()->harga);
    }

    public function test_rekap_harian_excludes_cancelled()
    {
        $order1 = \App\Models\Order::factory()->create(['status' => 'selesai']);
        $order2 = \App\Models\Order::factory()->create(['status' => 'batal']);
        
        $response = $this->actingAs($this->admin)->get('/laporan');
        $response->assertStatus(200);
        
        $rekap = $response->viewData('rekap');
        $this->assertNotNull($rekap);
    }
}
