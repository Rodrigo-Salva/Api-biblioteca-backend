<?php

namespace App\Http\Service;

use App\Models\Reservation;
use App\Models\Book;

class ReservationService
{
    public function getReservationsByUser($user)
    {
        return Reservation::with('book')->where('user_id', $user->id)->latest()->get();
    }

    public function createReservation($user, int $bookId)
    {
        $book = Book::findOrFail($bookId);
        
        if ($book->stock > 0) {
            throw new \Exception('No puedes reservar un libro que tiene stock disponible.');
        }

        return Reservation::create([
            'user_id' => $user->id,
            'book_id' => $bookId,
            'status' => 'pendiente'
        ]);
    }
}
