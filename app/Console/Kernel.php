<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Enviar recordatorios diariamente a las 9:00 AM
        $schedule->command('loans:send-reminders')
            ->dailyAt('09:00')
            ->timezone('America/Lima');

        // Enviar notificaciones de vencidos diariamente a las 10:00 AM
        $schedule->command('loans:send-overdue-notifications')
            ->dailyAt('10:00')
            ->timezone('America/Lima');
        
        $schedule->command('reservations:expire')->hourly();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}