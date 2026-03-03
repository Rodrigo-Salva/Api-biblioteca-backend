<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Loan;

class LoanReminderNotification extends Notification
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
        $daysLeft = now()->diffInDays($this->loan->due_date);
        
        return (new MailMessage)
            ->subject('Recordatorio: Devolución de libro próxima')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Tu préstamo del libro **"' . $this->loan->book->title . '"** vence pronto.')
            ->line('📅 Fecha de vencimiento: **' . $this->loan->due_date->format('d/m/Y') . '**')
            ->line('⏰ Quedan **' . $daysLeft . ' días** para devolverlo.')
            ->line('Recuerda que si no devuelves el libro a tiempo, se generará una multa de S/. 2.00 por día de retraso.')
            ->action('Ver mis préstamos', url('/api/loans'))
            ->line('¡Gracias por usar nuestra biblioteca!');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'loan_reminder',
            'loan_id' => $this->loan->id,
            'book_id' => $this->loan->book_id,
            'book_title' => $this->loan->book->title,
            'due_date' => $this->loan->due_date->format('Y-m-d'),
            'days_left' => now()->diffInDays($this->loan->due_date),
            'message' => 'Tu préstamo del libro "' . $this->loan->book->title . '" vence pronto',
        ];
    }
}