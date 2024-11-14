<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Reservation;

class ReservationAcceptedNotification extends Notification
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
            ->subject('Reservation Accepted')
            ->line("Your reservation for '{$this->reservation->book->title}' has been accepted.")
            ->line('Please pick up your book from the library.')
            ->line('Thank you for using our service!');
    }
}
