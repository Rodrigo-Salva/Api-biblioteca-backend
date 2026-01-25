<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Reservation;

class BookAvailableNotification extends Notification
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
            ->subject('¡Tu libro reservado está disponible!')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('El libro **"' . $this->reservation->book->title . '"** que reservaste ya está disponible.')
            ->line('Tienes **48 horas** para solicitar el préstamo antes de que expire tu reserva.')
            ->line('Fecha límite: **' . $this->reservation->expires_at->format('d/m/Y H:i') . '**')
            ->action('Solicitar préstamo ahora', url('/api/books/' . $this->reservation->book_id))
            ->line('Si no solicitas el préstamo a tiempo, la reserva pasará al siguiente usuario en la cola.')
            ->line('¡Disfruta tu lectura!');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'book_available',
            'reservation_id' => $this->reservation->id,
            'book_id' => $this->reservation->book_id,
            'book_title' => $this->reservation->book->title,
            'expires_at' => $this->reservation->expires_at->format('Y-m-d H:i:s'),
            'message' => 'El libro "' . $this->reservation->book->title . '" está disponible',
        ];
    }
}