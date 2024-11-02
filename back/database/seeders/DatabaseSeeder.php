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
        $users = User::factory(50)->create();

        // Create books with diverse categories
        $books = Book::factory(100)->create();

        // Create reservations with realistic patterns
        foreach ($users as $user) {
            $randomBooks = $books->random(rand(1, 5));

            foreach ($randomBooks as $book) {
                $status = $this->getRandomStatus();
                Reservation::factory()->create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'status' => $status,
                    'queue_position' => in_array($status, ['pending', 'ready']) ? rand(1, 5) : null,
                    'expires_at' => $status === 'ready' ? now()->addDays(2) : null,
                ]);
            }
        }
    }

    private function getRandomStatus(): string
    {
        return collect(['pending', 'ready', 'completed', 'cancelled'])
            ->random();
    }
}
