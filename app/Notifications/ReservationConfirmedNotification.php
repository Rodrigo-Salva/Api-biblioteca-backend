<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Reservation;

class ReservationConfirmedNotification extends Notification
{
    use Queueable;

    protected $reservation;
    protected $position;

    public function __construct(Reservation $reservation, int $position)
    {
        $this->reservation = $reservation;
        $this->position = $position;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Reserva Confirmada')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Tu reserva del libro **"' . $this->reservation->book->title . '"** ha sido confirmada.')
            ->line('Posición en la cola: **#' . $this->position . '**')
            ->line('Te notificaremos por email cuando el libro esté disponible.')
            ->action('Ver mis reservas', url('/api/reservations'))
            ->line('¡Gracias por tu paciencia!');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'reservation_confirmed',
            'reservation_id' => $this->reservation->id,
            'book_id' => $this->reservation->book_id,
            'book_title' => $this->reservation->book->title,
            'position' => $this->position,
            'message' => 'Reserva confirmada para "' . $this->reservation->book->title . '". Posición en cola: #' . $this->position,
        ];
    }
}