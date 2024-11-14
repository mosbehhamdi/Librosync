<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;

class CheckOverdueBooks extends Command
{
    protected $signature = 'books:check-overdue';
    protected $description = 'Check for overdue books and notify users';

    public function handle()
    {
        $overdueReservations = Reservation::where('status', Reservation::STATUS_DELIVERED)
            ->where('due_date', '<', now())
            ->with(['user', 'book'])
            ->get();

        foreach ($overdueReservations as $reservation) {
            // Notify user about overdue book
            $reservation->user->notify(new BookOverdueNotification($reservation));
        }

        $this->info("Processed {$overdueReservations->count()} overdue reservations");
    }
}
