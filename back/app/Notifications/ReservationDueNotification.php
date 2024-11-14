<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Reservation;

class ReservationDueNotification extends Notification
{
    private $reservation;
    private $isOverdue;

    public function __construct(Reservation $reservation, bool $isOverdue = false)
    {
        $this->reservation = $reservation;
        $this->isOverdue = $isOverdue;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $message = $this->isOverdue
            ? "The book '{$this->reservation->book->title}' is overdue"
            : "The book '{$this->reservation->book->title}' is due soon";

        return (new MailMessage)
            ->subject($this->isOverdue ? 'Book Overdue Notice' : 'Book Due Soon')
            ->line($message)
            ->line($this->isOverdue
                ? "Please return it as soon as possible."
                : "Due date: " . $this->reservation->due_date->format('Y-m-d'));
    }
}
