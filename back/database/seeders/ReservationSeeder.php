<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use App\Models\Reservation;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('is_admin', false)->get();
        $books = Book::all();

        // Create some random reservations
        foreach ($users as $user) {
            $randomBooks = $books->random(rand(0, 3));

            foreach ($randomBooks as $book) {
                $status = $this->getRandomStatus();

                Reservation::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'status' => $status,
                    'queue_position' => $status === 'pending' ? rand(1, 5) : null,
                    'expires_at' => $status === 'ready' ? now()->addDays(2) : null,
                ]);
            }
        }
    }

    private function getRandomStatus(): string
    {
        return collect(['pending', 'ready', 'delivered', 'cancelled'])
            ->random();
    }
}
