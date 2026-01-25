<?php

namespace App\Http\Service;

use App\Models\Fine;
use App\Models\User;

class FineService
{
    /**
     * Listar multas de un usuario
     */
    public function listByUser(User $user, ?string $status = null, int $perPage = 10)
    {
        $query = $user->fines()->with('loan.book');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Resumen de multas de un usuario
     */
    public function getUserSummary(User $user): array
    {
        $totalFines = $user->fines()->count();
        $pendingFines = $user->fines()->where('status', 'pendiente')->count();
        $totalAmountPending = $user->fines()->where('status', 'pendiente')->sum('amount');
        $totalAmountPaid = $user->fines()->where('status', 'pagada')->sum('amount');

        return [
            'total_fines' => $totalFines,
            'pending_fines' => $pendingFines,
            'total_amount_pending' => round($totalAmountPending, 2),
            'total_amount_paid' => round($totalAmountPaid, 2),
        ];
    }

    /**
     * Crear una multa manualmente (Admin)
     */
    public function create(array $data): Fine
    {
        $fine = Fine::create([
            'user_id' => $data['user_id'],
            'loan_id' => $data['loan_id'],
            'amount' => $data['amount'],
            'days_overdue' => $data['days_overdue'],
            'status' => 'pendiente',
        ]);

        return $fine->load(['user', 'loan.book']);
    }

    /**
     * Actualizar una multa (Admin)
     */
    public function update(Fine $fine, array $data): Fine
    {
        $fine->update($data);
        return $fine->load(['user', 'loan.book']);
    }

    /**
     * Pagar una multa
     */
    public function pay(Fine $fine): Fine
    {
        if ($fine->status === 'pagada') {
            throw new \Exception('Esta multa ya fue pagada');
        }

        $fine->update([
            'status' => 'pagada',
            'paid_at' => now(),
        ]);

        return $fine->load('loan.book');
    }

    /**
     * Eliminar una multa (Admin)
     */
    public function delete(Fine $fine): void
    {
        $fine->delete();
    }

    /**
     * Listar todas las multas (Admin)
     */
    public function listAll(?string $status = null, int $perPage = 20)
    {
        $query = Fine::with(['user', 'loan.book']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Estadísticas generales de multas (Admin)
     */
    public function getStatistics(): array
    {
        $totalFines = Fine::count();
        $pendingFines = Fine::where('status', 'pendiente')->count();
        $paidFines = Fine::where('status', 'pagada')->count();
        $totalAmountPending = Fine::where('status', 'pendiente')->sum('amount');
        $totalAmountCollected = Fine::where('status', 'pagada')->sum('amount');

        return [
            'total_fines' => $totalFines,
            'pending_fines' => $pendingFines,
            'paid_fines' => $paidFines,
            'total_amount_pending' => round($totalAmountPending, 2),
            'total_amount_collected' => round($totalAmountCollected, 2),
        ];
    }

    /**
     * Verificar si el usuario puede pagar la multa
     */
    public function canPay(Fine $fine, int $userId): bool
    {
        return $fine->user_id === $userId;
    }
}