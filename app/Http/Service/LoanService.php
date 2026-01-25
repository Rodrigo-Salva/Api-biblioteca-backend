<?php

namespace App\Http\Service;

use App\Models\Loan;
use App\Models\Book;
use App\Models\Fine;
use App\Models\Reservation;
use App\Notifications\LoanApprovedNotification;
use App\Notifications\FineGeneratedNotification;
use Carbon\Carbon;

class LoanService
{
    protected $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    public function listLoansForUser($user)
    {
        return $user->role === 'admin'
            ? Loan::with(['user', 'book'])->get()
            : Loan::with('book')->where('user_id', $user->id)->get();
    }

    public function createLoan($user, array $data)
    {
        // Verificar si tiene multas pendientes
        $hasPendingFines = Fine::where('user_id', $user->id)
            ->where('status', 'pendiente')
            ->exists();

        if ($hasPendingFines) {
            throw new \Exception('Tienes multas pendientes. Paga tus multas antes de solicitar un nuevo préstamo.');
        }

        // Verificar si tiene préstamos activos
        $tienePrestamo = Loan::where('user_id', $user->id)
            ->whereIn('status', ['pendiente', 'aprobado'])
            ->whereNull('return_date')
            ->exists();

        if ($tienePrestamo) {
            throw new \Exception('No puedes solicitar un nuevo préstamo hasta devolver el anterior.');
        }

        $book = Book::findOrFail($data['book_id']);
        
        // Verificar stock disponible
        if ($book->stock <= 0) {
            throw new \Exception('El libro no está disponible en este momento.');
        }

        // Si el usuario tiene una reserva disponible de este libro, usarla
        $reservation = Reservation::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', 'disponible')
            ->first();

        if ($reservation) {
            // Marcar reserva como usada (cancelada)
            $reservation->update(['status' => 'cancelada']);
        }
        
        $book->disminuirStock();

        $loan = Loan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'loan_date' => now(),
            'due_date' => now()->addDays(15),
            'return_date' => null,
            'status' => 'aprobado',
        ]);

        //ENVIAR NOTIFICACIÓN DE PRÉSTAMO APROBADO
        $user->notify(new LoanApprovedNotification($loan->load('book')));

        return $loan->load('book');
    }

    public function markAsReturned(Loan $loan)
    {
        if ($loan->return_date) {
            throw new \Exception('El préstamo ya ha sido devuelto.');
        }

        // Calcular si hay retraso y generar multa automáticamente
        $dueDate = Carbon::parse($loan->due_date);
        $returnDate = Carbon::now();

        $fine = null;

        if ($returnDate->greaterThan($dueDate)) {
            $daysOverdue = $returnDate->diffInDays($dueDate);
            $fineAmount = $daysOverdue * 2.00;

            // Crear la multa
            $fine = Fine::create([
                'user_id' => $loan->user_id,
                'loan_id' => $loan->id,
                'amount' => $fineAmount,
                'days_overdue' => $daysOverdue,
                'status' => 'pendiente',
            ]);

            //ENVIAR NOTIFICACIÓN DE MULTA GENERADA
            $loan->user->notify(new FineGeneratedNotification($fine->load('loan.book')));
        }

        // Marcar como devuelto y actualizar stock
        $loan->update([
            'return_date' => $returnDate,
            'status' => 'devuelto'
        ]);
        
        $loan->book->incrementarStock();

        // ✅ NOTIFICAR AL SIGUIENTE EN LA COLA DE RESERVAS
        $this->reservationService->notifyNextInQueue($loan->book_id);

        return $loan->load('book', 'fine');
    }

    public function getOverdueLoans()
    {
        return Loan::where('status', 'aprobado')
            ->whereNull('return_date')
            ->where('due_date', '<', now())
            ->with(['user', 'book'])
            ->get();
    }

    public function getLoansNearDue()
    {
        $threeDaysFromNow = now()->addDays(3);
        
        return Loan::where('status', 'aprobado')
            ->whereNull('return_date')
            ->whereBetween('due_date', [now(), $threeDaysFromNow])
            ->with(['user', 'book'])
            ->get();
    }
}