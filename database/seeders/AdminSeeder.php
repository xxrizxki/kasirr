<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $pw = env('ADMIN_PASSWORD');
        if (!$email || !$pw || strlen($pw) < 12) {
            $this->command->error('Isi ADMIN_EMAIL dan ADMIN_PASSWORD (minimal 12 karakter) di .env dulu.');
            return;
        }
        User::updateOrCreate(['email' => $email], [
            'name' => 'Admin',
            'password' => $pw,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }
}
