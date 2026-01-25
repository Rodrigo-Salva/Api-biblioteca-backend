<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Service\ReservationService;

class ExpireReservations extends Command
{
    protected $signature = 'reservations:expire';
    protected $description = 'Marcar reservas como expiradas después de 48 horas';

    protected $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        parent::__construct();
        $this->reservationService = $reservationService;
    }

    public function handle()
    {
        $count = $this->reservationService->expireReservations();

        $this->info("Se marcaron {$count} reservas como expiradas.");
        return 0;
    }
}