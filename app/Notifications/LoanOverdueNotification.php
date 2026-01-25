<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Loan;

class LoanOverdueNotification extends Notification
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
        $daysOverdue = $this->loan->daysOverdue();
        $fineAmount = $daysOverdue * 2.00;
        
        return (new MailMessage)
            ->subject('⚠️ Préstamo Vencido - Acción Requerida')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Tu préstamo del libro **"' . $this->loan->book->title . '"** está **vencido**.')
            ->line('Fecha de vencimiento: **' . $this->loan->due_date->format('d/m/Y') . '**')
            ->line('Días de retraso: **' . $daysOverdue . ' días**')
            ->line('Multa acumulada: **S/. ' . number_format($fineAmount, 2) . '**')
            ->line('Por favor, devuelve el libro lo antes posible para evitar multas adicionales.')
            ->action('Ver mis préstamos', url('/api/loans'))
            ->line('Si ya devolviste el libro, ignora este mensaje.');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'loan_overdue',
            'loan_id' => $this->loan->id,
            'book_id' => $this->loan->book_id,
            'book_title' => $this->loan->book->title,
            'due_date' => $this->loan->due_date->format('Y-m-d'),
            'days_overdue' => $this->loan->daysOverdue(),
            'estimated_fine' => $this->loan->daysOverdue() * 2.00,
            'message' => 'Tu préstamo del libro "' . $this->loan->book->title . '" está vencido',
        ];
    }
}