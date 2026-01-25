<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Fine;

class FineGeneratedNotification extends Notification
{
    use Queueable;

    protected $fine;

    public function __construct(Fine $fine)
    {
        $this->fine = $fine;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Multa Generada por Retraso')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Se ha generado una multa por la devolución tardía del libro **"' . $this->fine->loan->book->title . '"**.')
            ->line('Detalles de la multa:')
            ->line('• Días de retraso: **' . $this->fine->days_overdue . ' días**')
            ->line('• Monto: **S/. ' . number_format($this->fine->amount, 2) . '**')
            ->line('• Estado: **' . ucfirst($this->fine->status) . '**')
            ->line('Por favor, realiza el pago de la multa para poder solicitar nuevos préstamos.')
            ->action('Ver mis multas', url('/api/fines'))
            ->line('Gracias por tu comprensión.');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'fine_generated',
            'fine_id' => $this->fine->id,
            'loan_id' => $this->fine->loan_id,
            'book_title' => $this->fine->loan->book->title,
            'amount' => $this->fine->amount,
            'days_overdue' => $this->fine->days_overdue,
            'status' => $this->fine->status,
            'message' => 'Se generó una multa de S/. ' . number_format($this->fine->amount, 2) . ' por retraso',
        ];
    }
}