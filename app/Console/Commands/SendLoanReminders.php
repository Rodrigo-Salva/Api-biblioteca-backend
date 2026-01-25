<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Loan;
use App\Notifications\LoanReminderNotification;
use Carbon\Carbon;

class SendLoanReminders extends Command
{
    protected $signature = 'loans:send-reminders';
    protected $description = 'Enviar recordatorios de préstamos próximos a vencer (3 días antes)';

    public function handle()
    {
        $threeDaysFromNow = Carbon::now()->addDays(3)->toDateString();

        $loans = Loan::where('due_date', $threeDaysFromNow)
            ->where('status', 'aprobado')
            ->whereNull('return_date')
            ->with(['user', 'book'])
            ->get();

        $count = 0;
        foreach ($loans as $loan) {
            $loan->user->notify(new LoanReminderNotification($loan));
            $count++;
        }

        $this->info("Se enviaron {$count} recordatorios de devolución.");
        return 0;
    }
}