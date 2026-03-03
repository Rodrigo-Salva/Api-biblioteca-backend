<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Reservation;

class ReservationCancelledNotification extends Notification
{
    use Queueable;

    protected $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Reserva Cancelada')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Tu reserva del libro **"' . $this->reservation->book->title . '"** ha sido cancelada.')
            ->line('Puedes hacer una nueva reserva cuando lo desees.')
            ->action('Ver libros disponibles', url('/api/books/available'))
            ->line('Gracias por usar nuestra biblioteca.');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'reservation_cancelled',
            'reservation_id' => $this->reservation->id,
            'book_id' => $this->reservation->book_id,
            'book_title' => $this->reservation->book->title,
            'message' => 'Tu reserva del libro "' . $this->reservation->book->title . '" fue cancelada',
        ];
    }
}