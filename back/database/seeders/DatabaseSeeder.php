<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Book;
use App\Models\Reservation;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_admin' => true
        ]);

        // Create regular users
        $users = User::factory(5)->create();

        // Create books first
        $books = Book::factory(20)->create();

        // Create some reservations for each user
        foreach ($users as $user) {
            // Create 1-2 pending reservations
            Reservation::factory()
                ->pending()
                ->count(rand(1, 2))
                ->create([
                    'user_id' => $user->id,
                    'book_id' => $books->random()->id,
                ]);

            // Create 1 ready reservation
            Reservation::factory()
                ->ready()
                ->create([
                    'user_id' => $user->id,
                    'book_id' => $books->random()->id,
                ]);
        }
    }
}
