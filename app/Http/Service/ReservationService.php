<?php

namespace App\Http\Service;

use App\Models\Reservation;
use App\Models\Book;
use App\Models\User;
use App\Notifications\BookAvailableNotification;
use App\Notifications\ReservationCancelledNotification;
use App\Notifications\ReservationConfirmedNotification;

class ReservationService
{
    /**
     * Listar reservas del usuario
     */
    public function listByUser(User $user, ?string $status = null)
    {
        $query = $user->reservations()->with('book');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('reserved_at', 'desc')->get();
    }

    public function getReservationsByUser($user)
    {
        return $this->listByUser($user);
    }

    /**
     * Crear una reserva
     */
    public function create(User $user, int $bookId)
    {
        $book = Book::findOrFail($bookId);

        if ($book->stock > 0) {
            throw new \Exception('No puedes reservar un libro que tiene stock disponible.');
        }

        // Verificar si ya tiene una reserva activa de este libro
        if ($user->hasActiveReservation($bookId)) {
            throw new \Exception('Ya tienes una reserva activa para este libro.');
        }

        // Verificar si tiene préstamos activos del mismo libro
        $hasActiveLoan = $user->loans()
            ->where('book_id', $bookId)
            ->whereNull('return_date')
            ->exists();

        if ($hasActiveLoan) {
            throw new \Exception('Ya tienes un préstamo activo de este libro.');
        }

        // Crear la reserva
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'book_id' => $bookId,
            'status' => 'pendiente',
            'reserved_at' => now(),
        ]);

        // Calcular posición en la cola
        $position = $this->getQueuePosition($reservation);

        // Enviar notificación de confirmación
        try {
            $user->notify(new ReservationConfirmedNotification($reservation->load('book'), $position));
        } catch (\Exception $e) {
            // Log or ignore
        }

        return $reservation->load('book');
    }

    public function createReservation($user, int $bookId)
    {
        return $this->create($user, $bookId);
    }

    /**
     * Cancelar una reserva
     */
    public function cancel(Reservation $reservation)
    {
        if ($reservation->status === 'cancelada') {
            throw new \Exception('Esta reserva ya fue cancelada.');
        }

        $reservation->cancel();

        // Enviar notificación
        try {
            $reservation->user->notify(new ReservationCancelledNotification($reservation->load('book')));
        } catch (\Exception $e) {
            // Ignore
        }

        // Notificar al siguiente en la cola si el libro está disponible
        $this->notifyNextInQueue($reservation->book_id);

        return $reservation;
    }

    /**
     * Notificar al siguiente en la cola cuando un libro esté disponible
     */
    public function notifyNextInQueue(int $bookId)
    {
        $book = Book::findOrFail($bookId);

        // Si hay stock disponible
        if ($book->stock > 0) {
            // Buscar la primera reserva pendiente (FIFO)
            $nextReservation = Reservation::where('book_id', $bookId)
                ->where('status', 'pendiente')
                ->orderBy('reserved_at', 'asc')
                ->first();

            if ($nextReservation) {
                // Marcar como disponible
                $nextReservation->markAsAvailable();

                // Notificar al usuario
                try {
                    $nextReservation->user->notify(
                        new BookAvailableNotification($nextReservation->load('book'))
                    );
                } catch (\Exception $e) {
                    // Ignore
                }

                return $nextReservation;
            }
        }

        return null;
    }

    /**
     * Marcar reservas expiradas
     */
    public function expireReservations()
    {
        $expiredReservations = Reservation::where('status', 'disponible')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expiredReservations as $reservation) {
            $reservation->markAsExpired();

            // Notificar al siguiente en la cola
            $this->notifyNextInQueue($reservation->book_id);
        }

        return $expiredReservations->count();
    }

    /**
     * Obtener posición en la cola
     */
    public function getQueuePosition(Reservation $reservation): int
    {
        return Reservation::where('book_id', $reservation->book_id)
            ->where('status', 'pendiente')
            ->where('reserved_at', '<=', $reservation->reserved_at)
            ->count();
    }

    /**
     * Listar todas las reservas (Admin)
     */
    public function listAll(?string $status = null)
    {
        $query = Reservation::with(['user', 'book']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('reserved_at', 'desc')->paginate(20);
    }

    /**
     * Actualizar estado de reserva (Admin)
     */
    public function updateStatus(Reservation $reservation, string $status)
    {
        $reservation->update(['status' => $status]);

        if ($status === 'cancelada') {
            try {
                $reservation->user->notify(new ReservationCancelledNotification($reservation->load('book')));
            } catch (\Exception $e) {
                // Ignore
            }
        }

        return $reservation->load(['user', 'book']);
    }

    /**
     * Eliminar reserva (Admin)
     */
    public function delete(Reservation $reservation): void
    {
        $bookId = $reservation->book_id;
        $reservation->delete();

        // Notificar al siguiente en la cola
        $this->notifyNextInQueue($bookId);
    }

    /**
     * Estadísticas de reservas
     */
    public function getStatistics(): array
    {
        return [
            'total_reservations' => Reservation::count(),
            'pending_reservations' => Reservation::where('status', 'pendiente')->count(),
            'available_reservations' => Reservation::where('status', 'disponible')->count(),
            'expired_reservations' => Reservation::where('status', 'expirada')->count(),
            'cancelled_reservations' => Reservation::where('status', 'cancelada')->count(),
        ];
    }
}
