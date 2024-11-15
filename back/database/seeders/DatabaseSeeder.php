<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Book;
use App\Models\Reservation;
use Carbon\Carbon;

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
            // Create active reservations
            $activeBooks = $books->random(rand(2, 4));
            foreach ($activeBooks as $book) {
                $status = collect([
                    Reservation::STATUS_PENDING,
                    Reservation::STATUS_READY,
                    Reservation::STATUS_ACCEPTED,
                    Reservation::STATUS_DELIVERED
                ])->random();

                $baseDate = now()->subDays(rand(1, 14));
                $reservation = new Reservation();
                $reservation->user_id = $user->id;
                $reservation->book_id = $book->id;
                $reservation->status = $status;
                $reservation->created_at = $baseDate;

                // Initialize status history
                $statusHistory = [
                    'created' => $baseDate->toDateTimeString()
                ];

                // Set specific fields based on status
                switch ($status) {
                    case Reservation::STATUS_PENDING:
                        $reservation->queue_position = rand(1, 5);
                        break;

                    case Reservation::STATUS_READY:
                        $readyDate = $baseDate->copy()->addDays(1);
                        $reservation->expires_at = now()->addDays(2);
                        $statusHistory['ready'] = $readyDate->toDateTimeString();
                        break;

                    case Reservation::STATUS_ACCEPTED:
                        $readyDate = $baseDate->copy()->addDays(1);
                        $acceptedDate = $readyDate->copy()->addDays(1);
                        $statusHistory['ready'] = $readyDate->toDateTimeString();
                        $statusHistory['accepted'] = $acceptedDate->toDateTimeString();
                        $reservation->accepted_at = $acceptedDate;
                        break;

                    case Reservation::STATUS_DELIVERED:
                        $readyDate = $baseDate->copy()->addDays(1);
                        $acceptedDate = $readyDate->copy()->addDays(1);
                        $deliveredDate = $acceptedDate->copy()->addDays(1);
                        $statusHistory['ready'] = $readyDate->toDateTimeString();
                        $statusHistory['accepted'] = $acceptedDate->toDateTimeString();
                        $statusHistory['delivered'] = $deliveredDate->toDateTimeString();
                        $reservation->accepted_at = $acceptedDate;
                        $reservation->delivered_at = $deliveredDate;
                        $reservation->due_date = $deliveredDate->copy()->addDays(14);
                        $book->decrement('available_copies');
                        break;
                }

                $reservation->status_history = $statusHistory;
                $reservation->save();
            }

            // Create past reservations
            $pastBooks = $books->diff($activeBooks)->random(rand(2, 4));
            foreach ($pastBooks as $book) {
                $status = collect([
                    Reservation::STATUS_RETURNED,
                    Reservation::STATUS_CANCELLED
                ])->random();

                $baseDate = now()->subDays(rand(15, 45));
                $reservation = new Reservation();
                $reservation->user_id = $user->id;
                $reservation->book_id = $book->id;
                $reservation->status = $status;
                $reservation->created_at = $baseDate;

                // Initialize status history
                $statusHistory = [
                    'created' => $baseDate->toDateTimeString()
                ];

                if ($status === Reservation::STATUS_RETURNED) {
                    $readyDate = $baseDate->copy()->addDays(1);
                    $acceptedDate = $readyDate->copy()->addDays(1);
                    $deliveredDate = $acceptedDate->copy()->addDays(1);
                    $returnedDate = $deliveredDate->copy()->addDays(rand(7, 14));

                    $statusHistory['ready'] = $readyDate->toDateTimeString();
                    $statusHistory['accepted'] = $acceptedDate->toDateTimeString();
                    $statusHistory['delivered'] = $deliveredDate->toDateTimeString();
                    $statusHistory['returned'] = $returnedDate->toDateTimeString();

                    $reservation->accepted_at = $acceptedDate;
                    $reservation->delivered_at = $deliveredDate;
                    $reservation->returned_at = $returnedDate;
                    $reservation->due_date = $deliveredDate->copy()->addDays(14);
                } else {
                    // Cancelled status
                    $cancelledDate = $baseDate->copy()->addDays(rand(1, 3));
                    $statusHistory['cancelled'] = $cancelledDate->toDateTimeString();
                }

                $reservation->status_history = $statusHistory;
                $reservation->save();
            }
        }
    }
}
