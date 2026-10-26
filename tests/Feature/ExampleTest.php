<?php

use App\Models\User;

test('guest redirected to login', function () {
    $response = $this->get('/');
    $response->assertRedirect('/login');
});

test('authenticated user can access kasir', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get('/');
    $response->assertStatus(200);
});
