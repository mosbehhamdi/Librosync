<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Notifications\ReservationDueNotification;
use Illuminate\Console\Command;

class CheckReservationDueDates extends Command
{
    protected $signature = 'reservations:check-due-dates';
    protected $description = 'Check for due and overdue reservations';

    public function handle()
    {
        // Check for books due soon (2 days before)
        $dueSoon = Reservation::where('status', 'delivered')
            ->whereDate('due_date', '=', now()->addDays(2))
            ->whereNull('due_notification_sent')
            ->get();

        foreach ($dueSoon as $reservation) {
            $reservation->user->notify(new ReservationDueNotification($reservation));
            $reservation->due_notification_sent = true;
            $reservation->notification_sent_at = now();
            $reservation->save();
        }

        // Check for overdue books
        $overdue = Reservation::where('status', 'delivered')
            ->where('due_date', '<', now())
            ->whereNull('overdue_notification_sent')
            ->get();

        foreach ($overdue as $reservation) {
            $reservation->user->notify(new ReservationDueNotification($reservation, true));
            $reservation->overdue_notification_sent = true;
            $reservation->notification_sent_at = now();
            $reservation->save();
        }

        $this->info('Notifications sent successfully.');
    }
}
