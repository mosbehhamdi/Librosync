<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationAcceptedNotification;
use App\Notifications\ReservationReadyNotification;
use App\Notifications\ReservationDueNotification;
use Illuminate\Console\Command;

class TestNotifications extends Command
{
    protected $signature = 'notifications:test {type} {--email=mesbahhamdidsi@gmail.com}';
    protected $description = 'Test different types of notifications';

    public function handle()
    {
        $type = $this->argument('type');
        $email = $this->option('email');

        // Get or create a test user with the specified email
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );

        // Get or create a test reservation for this user
        $reservation = Reservation::with(['user', 'book'])->where('user_id', $user->id)->first();

        if (!$reservation) {
            $this->info('Creating a test reservation...');
            // Get the first book or handle the case when no books exist
            $book = \App\Models\Book::first();
            if (!$book) {
                $this->error('No books found in database');
                return;
            }

            $reservation = Reservation::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'status' => 'accepted',
                'delivered_at' => now(),
                'due_date' => now()->addDays(14)
            ]);

            $reservation->load(['user', 'book']);
        }

        try {
            $this->info('Sending notification to: ' . $email);

            switch ($type) {
                case 'accepted':
                    $user->notify(new ReservationAcceptedNotification($reservation));
                    $this->info('Sent acceptance notification');
                    break;

                case 'ready':
                    $user->notify(new ReservationReadyNotification($reservation));
                    $this->info('Sent ready for pickup notification');
                    break;

                case 'due':
                    $user->notify(new ReservationDueNotification($reservation));
                    $this->info('Sent due soon notification');
                    break;

                case 'overdue':
                    $user->notify(new ReservationDueNotification($reservation, true));
                    $this->info('Sent overdue notification');
                    break;

                default:
                    $this->error('Invalid notification type');
                    return;
            }

            $this->info('Test email sent successfully!');
            $this->info('Check your inbox at: ' . $email);

        } catch (\Exception $e) {
            $this->error('Error sending notification: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
        }
    }
}
