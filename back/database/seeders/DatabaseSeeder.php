<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'email_verified_at' => now(),
            'is_admin' => true
        ]);

        // Create some regular users if needed
        User::factory(10)->create();

        // Run the BookSeeder
        $this->call([
            BookSeeder::class
        ]);
    }
}
