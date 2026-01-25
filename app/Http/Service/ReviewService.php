<?php

namespace App\Http\Service;

use App\Models\Review;
use App\Models\Book;

class ReviewService
{
    /**
     * Listar reseñas de un libro
     */
    public function listByBook(Book $book, int $perPage = 10)
    {
        return $book->reviews()
            ->with('user:id,name')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Crear una reseña
     */
    public function create(Book $book, int $userId, array $data)
    {
        // Verificar si ya existe una reseña
        $existingReview = Review::where('user_id', $userId)
            ->where('book_id', $book->id)
            ->first();

        if ($existingReview) {
            throw new \Exception('Ya has hecho una reseña de este libro');
        }

        $review = Review::create([
            'user_id' => $userId,
            'book_id' => $book->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        return $review->load('user:id,name');
    }

    /**
     * Actualizar una reseña
     */
    public function update(Review $review, array $data)
    {
        $review->update($data);
        return $review->load('user:id,name');
    }

    /**
     * Eliminar una reseña
     */
    public function delete(Review $review): void
    {
        $review->delete();
    }

    /**
     * Obtener rating promedio de un libro
     */
    public function getAverageRating(Book $book): array
    {
        $average = $book->reviews()->avg('rating');
        $count = $book->reviews()->count();

        return [
            'average_rating' => $average ? round($average, 2) : 0,
            'total_reviews' => $count,
        ];
    }

    /**
     * Verificar si el usuario puede modificar la reseña
     */
    public function canModify(Review $review, int $userId, string $userRole): bool
    {
        return $review->user_id === $userId || $userRole === 'admin';
    }
}