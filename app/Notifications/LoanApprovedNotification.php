<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Loan;

class LoanApprovedNotification extends Notification
{
    use Queueable;

    protected $loan;

    public function __construct(Loan $loan)
    {
        $this->loan = $loan;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('✅ Préstamo Aprobado')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Tu préstamo del libro **"' . $this->loan->book->title . '"** ha sido aprobado.')
            ->line('Fecha de préstamo: **' . $this->loan->loan_date->format('d/m/Y') . '**')
            ->line('Fecha de devolución: **' . $this->loan->due_date->format('d/m/Y') . '**')
            ->line('Tienes **15 días** para disfrutar de tu lectura.')
            ->action('Ver detalles del préstamo', url('/api/loans/' . $this->loan->id))
            ->line('¡Disfruta tu lectura!');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'loan_approved',
            'loan_id' => $this->loan->id,
            'book_id' => $this->loan->book_id,
            'book_title' => $this->loan->book->title,
            'loan_date' => $this->loan->loan_date->format('Y-m-d'),
            'due_date' => $this->loan->due_date->format('Y-m-d'),
            'message' => 'Tu préstamo del libro "' . $this->loan->book->title . '" ha sido aprobado',
        ];
    }
}