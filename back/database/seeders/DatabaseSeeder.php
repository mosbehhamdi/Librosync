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

        // Create books
        $books = Book::factory(100)->create();

        foreach ($users as $user) {
            // Create active reservations (pending, ready, accepted, delivered)
            $activeBooks = $books->random(rand(2, 4));
            foreach ($activeBooks as $book) {
                $status = collect([
                    Reservation::STATUS_PENDING,
                    Reservation::STATUS_READY,
                    Reservation::STATUS_ACCEPTED,
                    Reservation::STATUS_DELIVERED
                ])->random();

                $reservation = new Reservation();
                $reservation->user_id = $user->id;
                $reservation->book_id = $book->id;
                $reservation->status = $status;

                // Set specific fields based on status
                switch ($status) {
                    case Reservation::STATUS_PENDING:
                        $reservation->queue_position = rand(1, 5);
                        break;
                    case Reservation::STATUS_READY:
                        $reservation->expires_at = now()->addDays(2);
                        break;
                    case Reservation::STATUS_DELIVERED:
                        $delivered_at = now()->subDays(rand(1, 13));
                        $reservation->delivered_at = $delivered_at;
                        $reservation->due_date = $delivered_at->copy()->addDays(14);
                        $book->decrement('available_copies');
                        break;
                }

                $reservation->save();
            }

            // Create past reservations (delivered, cancelled)
            $pastBooks = $books->diff($activeBooks)->random(rand(2, 4));
            foreach ($pastBooks as $book) {
                $status = collect([
                    Reservation::STATUS_RETURNED,
                    Reservation::STATUS_CANCELLED
                ])->random();

                $reservation = new Reservation();
                $reservation->user_id = $user->id;
                $reservation->book_id = $book->id;
                $reservation->status = $status;

                if ($status === Reservation::STATUS_RETURNED) {
                    $delivered_at = now()->subDays(rand(15, 30));
                    $reservation->delivered_at = $delivered_at;
                    $reservation->due_date = $delivered_at->copy()->addDays(14);
                }

                $reservation->created_at = now()->subDays(rand(1, 30));
                $reservation->save();
            }
        }
    }
}
