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
    protected $notificationService;

    public function __construct(ReservationService $reservationService, NotificationService $notificationService)
    {
        $this->reservationService = $reservationService;
        $this->notificationService = $notificationService;
    }

    public function listLoansForUser($user)
    {
        return $user->role === 'admin'
            ? Loan::with(['user', 'book'])->paginate(15)
            : Loan::with('book')->where('user_id', $user->id)->paginate(15);
    }

    public function createLoan($user, array $data)
    {
        // 1. Verificar si tiene multas pendientes
        $hasPendingFines = Fine::where('user_id', $user->id)
            ->where('status', 'pendiente')
            ->exists();

        if ($hasPendingFines) {
            throw new \Exception('Tienes multas pendientes. Paga tus multas antes de solicitar un nuevo préstamo.');
        }

        // 2. Verificar si tiene préstamos activos y límite
        $prestamosActivos = Loan::where('user_id', $user->id)
            ->whereIn('status', ['pendiente', 'aprobado'])
            ->whereNull('return_date')
            ->count();

        if ($prestamosActivos >= 3) {
            throw new \Exception('Has alcanzado el límite máximo de 3 préstamos activos.');
        }

        $book = Book::findOrFail($data['book_id']);
        
        // 3. Verificar stock disponible
        if ($book->stock <= 0) {
            throw new \Exception('El libro no está disponible en este momento.');
        }

        // 4. Buscar unidad disponible si existe el sistema de unidades
        $unit = null;
        if (method_exists($book, 'units')) {
            $unit = $book->units()->where('status', 'disponible')->first();
            if ($unit) {
                $unit->update(['status' => 'prestado']);
            }
        }

        // 5. Si el usuario tiene una reserva disponible de este libro, usarla
        $reservation = Reservation::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', 'disponible')
            ->first();

        if ($reservation) {
            $reservation->update(['status' => 'cancelada']);
        }
        
        $book->disminuirStock();

        $loan = Loan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'book_unit_id' => $unit ? $unit->id : null,
            'loan_date' => now(),
            'due_date' => now()->addDays(15),
            'return_date' => null,
            'status' => 'aprobado',
        ]);

        // 6. Notificaciones
        try {
            $user->notify(new LoanApprovedNotification($loan->load('book')));
        } catch (\Exception $e) {
            // Fallback to custom notification service if available
            $this->notificationService->createNotification(
                $user->id,
                'prestamo_creado',
                'Préstamo Confirmado',
                "Has solicitado '{$book->title}'. Debes devolverlo antes del {$loan->due_date}."
            );
        }

        return $loan->load('book');
    }

    public function markAsReturned(Loan $loan)
    {
        if ($loan->return_date) {
            throw new \Exception('El préstamo ya ha sido devuelto.');
        }

        $now = now();
        $dueDate = Carbon::parse($loan->due_date);
        $fine = null;

        // Calcular si hay retraso y generar multa automáticamente
        if ($now->greaterThan($dueDate)) {
            $daysOverdue = $now->diffInDays($dueDate);
            $fineAmount = $daysOverdue * 2.00;

            // Crear la multa
            $fine = Fine::create([
                'user_id' => $loan->user_id,
                'loan_id' => $loan->id,
                'amount' => $fineAmount,
                'days_overdue' => $daysOverdue,
                'status' => 'pendiente',
            ]);

            // Notificar multa
            try {
                $loan->user->notify(new FineGeneratedNotification($fine->load('loan.book')));
            } catch (\Exception $e) {
                // Ignore if notification fails
            }
        }

        // Marcar como devuelto y actualizar stock
        $loan->update([
            'return_date' => $now,
            'status' => 'devuelto'
        ]);
        
        // Liberar unidad
        if ($loan->unit) {
            $loan->unit->update(['status' => 'disponible']);
        }

        $loan->book->incrementarStock();

        // Notificar al siguiente en la cola de reservas
        $this->reservationService->notifyNextInQueue($loan->book_id);

        return $loan->load('book', 'fine');
    }

    public function payFine(Loan $loan)
    {
        $fine = Fine::where('loan_id', $loan->id)->where('status', 'pendiente')->first();
        if (!$fine && (!$loan->fine_amount || $loan->is_paid)) {
            throw new \Exception('No hay multas pendientes para este préstamo.');
        }

        if ($fine) {
            $fine->update(['status' => 'pagado']);
        }
        
        $loan->update(['is_paid' => true]);
        
        return $loan;
    }

    public function renew(Loan $loan)
    {
        // 1. Verificar que el préstamo esté activo
        if ($loan->return_date) {
            throw new \Exception('No se puede renovar un préstamo ya devuelto.');
        }

        // 2. Verificar que no haya reservas pendientes para este libro
        $hasReservations = Reservation::where('book_id', $loan->book_id)
            ->whereIn('status', ['pendiente', 'disponible'])
            ->exists();

        if ($hasReservations) {
            throw new \Exception('No se puede renovar porque hay otros usuarios esperando este libro.');
        }

        // 3. Verificar límite de renovaciones (ej. máximo 1 vez)
        if ($loan->renewal_count >= 1) {
            throw new \Exception('Este préstamo ya ha sido renovado el máximo de veces permitido.');
        }

        // 4. Actualizar fecha de vencimiento (añadir 7 días)
        $loan->update([
            'due_date' => Carbon::parse($loan->due_date)->addDays(7),
            'renewal_count' => ($loan->renewal_count ?? 0) + 1
        ]);

        return $loan->load('book');
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