<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class KasirMenuTest extends TestCase
{
    public function test_admin_sees_logout_and_admin_harga_link()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($admin)->get('/');
        $response->assertStatus(200);
        $response->assertSee('Keluar');
        $response->assertSee('Admin harga');
    }

    public function test_regular_user_sees_logout_not_admin_link()
    {
        $user = User::factory()->create(['role' => 'user']);
        
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee('Keluar');
        $response->assertDontSee('Admin harga');
    }

    public function test_guest_redirected_to_login()
    {
        $response = $this->get('/');
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
}
