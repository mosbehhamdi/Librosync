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

        foreach ($users as $user) {
            $deliveredBooks = $books->random(rand(1, 2));
            foreach ($deliveredBooks as $book) {
                $delivered_at = now()->subDays(rand(1, 13));
                Reservation::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'status' => 'delivered',
                    'delivered_at' => $delivered_at,
                    'due_date' => $delivered_at->copy()->addDays(14),
                ]);
                $book->decrement('available_copies');
            }

            // Create other random reservations
            $otherBooks = $books->diff($deliveredBooks)->random(rand(0, 2));
            foreach ($otherBooks as $book) {
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
        return collect([
            Reservation::STATUS_PENDING,
            Reservation::STATUS_READY,
            Reservation::STATUS_ACCEPTED,
            Reservation::STATUS_DELIVERED,
            Reservation::STATUS_RETURNED,
            Reservation::STATUS_CANCELLED
        ])->random();
    }
}
