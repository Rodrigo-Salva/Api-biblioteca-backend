<?php

namespace App\Http\Service;

use App\Models\Loan;
use App\Models\Book;

class LoanService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
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
        // 1. Verificar límite de préstamos
        $prestamosActivos = Loan::where('user_id', $user->id)
            ->whereIn('status', ['pendiente', 'aprobado'])
            ->whereNull('return_date')
            ->count();

        if ($prestamosActivos >= 3) {
            throw new \Exception('Has alcanzado el límite máximo de 3 préstamos activos.');
        }

        // 2. Verificar multas pendientes
        $multasPendientes = Loan::where('user_id', $user->id)
            ->where('fine_amount', '>', 0)
            ->where('is_paid', false)
            ->exists();

        if ($multasPendientes) {
            throw new \Exception('No puedes solicitar nuevos préstamos porque tienes multas pendientes de pago.');
        }

        $book = Book::findOrFail($data['book_id']);
        
        // 3. Buscar unidad disponible
        $unit = $book->units()->where('status', 'disponible')->first();
        
        if (!$unit && $book->stock <= 0) {
             throw new \Exception('El libro no está disponible (sin stock)');
        }

        $book->disminuirStock();
        
        if ($unit) {
            $unit->update(['status' => 'prestado']);
        }

        $loan = Loan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'book_unit_id' => $unit ? $unit->id : null,
            'loan_date' => now(),
            'due_date' => now()->addDays(15),
            'return_date' => null,
            'status' => 'aprobado',
        ]);

        $this->notificationService->createNotification(
            $user->id,
            'prestamo_creado',
            'Préstamo Confirmado',
            "Has solicitado '{$book->title}'. Debes devolverlo antes del {$loan->due_date}."
        );

        return $loan;
    }

    public function markAsReturned(Loan $loan)
    {
        if ($loan->return_date) {
            throw new \Exception('El préstamo ya ha sido devuelto.');
        }

        $now = now();
        $loan->return_date = $now;
        
        // Calcular multa (1.00 por día de retraso)
        $dueDate = \Carbon\Carbon::parse($loan->due_date);
        if ($now->greaterThan($dueDate)) {
            $daysLate = $now->diffInDays($dueDate);
            $loan->fine_amount = $daysLate * 1.00;
        }

        $loan->status = 'devuelto';
        $loan->save();

        // Liberar unidad
        if ($loan->unit) {
            $loan->unit->update(['status' => 'disponible']);
        }
        
        $loan->book->incrementarStock();

        $message = "Has devuelto '{$loan->book->title}' correctamente.";
        if ($loan->fine_amount > 0) {
            $message .= " Se ha generado una multa de ${$loan->fine_amount} por retraso.";
        }

        $this->notificationService->createNotification(
            $loan->user_id,
            'prestamo_devuelto',
            'Libro Devuelto',
            $message
        );

        return $loan;
    }

    public function payFine(Loan $loan)
    {
        if ($loan->fine_amount <= 0 || $loan->is_paid) {
            throw new \Exception('No hay multas pendientes para este préstamo.');
        }

        $loan->update(['is_paid' => true]);
        return $loan;
    }
}
