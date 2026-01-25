<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Loan;
use App\Notifications\LoanOverdueNotification;

class SendOverdueNotifications extends Command
{
    protected $signature = 'loans:send-overdue-notifications';
    protected $description = 'Enviar notificaciones de préstamos vencidos';

    public function handle()
    {
        $loans = Loan::where('status', 'aprobado')
            ->whereNull('return_date')
            ->where('due_date', '<', now())
            ->with(['user', 'book'])
            ->get();

        $count = 0;
        foreach ($loans as $loan) {
            $loan->user->notify(new LoanOverdueNotification($loan));
            $count++;
        }

        $this->info("Se enviaron {$count} notificaciones de préstamos vencidos.");
        return 0;
    }
}