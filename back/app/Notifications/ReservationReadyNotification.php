<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Reservation;

class ReservationReadyNotification extends Notification
{
    private $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Book Ready for Pickup')
            ->line("The book '{$this->reservation->book->title}' is now ready for pickup.")
            ->line("Please pick it up within 48 hours.")
            ->line("After that, your reservation will be cancelled automatically.");
    }
}
