<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    public function test_unauthenticated_user_cannot_access_admin_harga()
    {
        $response = $this->get('/admin/harga');
        $response->assertStatus(302);
        $this->assertTrue(auth()->guest());
    }

    public function test_regular_user_cannot_access_admin_harga()
    {
        $user = User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($user)->get('/admin/harga');
        $response->assertStatus(403);
        $response->assertSee('Unauthorized');
    }

    public function test_admin_user_can_access_admin_harga()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get('/admin/harga');
        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_put_harga()
    {
        $user = User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($user)->put('/admin/harga', [
            'harga' => [1 => 50000],
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_user_can_put_harga()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->put('/admin/harga', [
            'harga' => [1 => 50000],
        ]);
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_regular_user_cannot_post_massal()
    {
        $user = User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($user)->post('/admin/harga/massal', [
            'category_id' => 1,
            'persentase' => 10,
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_user_can_post_massal()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->post('/admin/harga/massal', [
            'category_id' => 1,
            'persentase' => 10,
        ]);
        $this->assertNotEquals(403, $response->getStatusCode());
    }
}
