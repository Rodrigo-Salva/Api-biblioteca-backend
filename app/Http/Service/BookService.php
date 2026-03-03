<?php

namespace App\Http\Service;

use App\Models\Book;
use Illuminate\Support\Facades\Storage;

class BookService
{
    public function list($filters = [])
    {
        if ($filters === true || (is_array($filters) && isset($filters['all']))) {
            return Book::with(['author', 'category'])->get()->transform(function ($book) {
                if ($book->cover_image) {
                    $book->cover_image_url = asset('storage/' . $book->cover_image);
                }
                return $book;
            });
        }

        $filters = is_array($filters) ? $filters : [];
        $perPage = $filters['per_page'] ?? 10;
        $query = Book::with(['author', 'category']);

        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function($query) use ($q) {
                $query->where('title', 'like', "%$q%")
                      ->orWhere('isbn', 'like', "%$q%")
                      ->orWhereHas('author', function($query) use ($q) {
                          $query->where('name', 'like', "%$q%");
                      });
            });
        }

        if (!empty($filters['category_id'])) {
            $query->whereIn('category_id', explode(',', $filters['category_id']));
        }

        if (!empty($filters['author_id'])) {
            $query->whereIn('author_id', explode(',', $filters['author_id']));
        }

        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($book) {
            if ($book->cover_image) {
                $book->cover_image_url = asset('storage/' . $book->cover_image);
            }
            return $book;
        });

        return $paginator;
    }

    public function available(array $filters = [])
    {
        $perPage = $filters['per_page'] ?? 12;
        $query = Book::where('stock', '>', 0)->with(['author', 'category']);

        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($query) use ($q) {
                $query->where('title', 'like', "%$q%")
                      ->orWhere('isbn', 'like', "%$q%")
                      ->orWhereHas('author', function ($query) use ($q) {
                          $query->where('name', 'like', "%$q%");
                      });
            });
        }

        if (!empty($filters['category_id'])) {
            $query->whereIn('category_id', explode(',', $filters['category_id']));
        }

        if (!empty($filters['author_id'])) {
            $query->whereIn('author_id', explode(',', $filters['author_id']));
        }

        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($book) {
            if ($book->cover_image) {
                $book->cover_image_url = asset('storage/' . $book->cover_image);
            }
            return $book;
        });

        return $paginator;
    }

    public function create(array $data, $coverImageFile = null): Book
    {
        if ($coverImageFile) {
            $data['cover_image'] = $coverImageFile->store('covers', 'public');
        }

        return Book::create($data);
    }

    public function update(Book $book, array $data, $coverImageFile = null): Book
    {
        if ($coverImageFile) {
            if ($book->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }

            $data['cover_image'] = $coverImageFile->store('covers', 'public');
        }

        $book->update($data);
        return $book;
    }

    public function delete(Book $book): void
    {
        if ($book->cover_image) {
            Storage::disk('public')->delete($book->cover_image);
        }

        $book->delete();
    }

    public function get(Book $book): Book
    {
        $book->load(['author', 'category']);
        if ($book->cover_image) {
            $book->cover_image_url = asset('storage/' . $book->cover_image);
        }
        return $book;
    }

    public function getRecommendations(\App\Models\User $user): \Illuminate\Support\Collection
    {
        // Obtener categorías de los libros que el usuario ha pedido prestados o marcado como favoritos
        $loanedCategoryIds = \App\Models\Loan::where('user_id', $user->id)
            ->join('book_units', 'loans.book_unit_id', '=', 'book_units.id')
            ->join('books', 'book_units.book_id', '=', 'books.id')
            ->pluck('books.category_id')
            ->unique();

        $favoriteCategoryIds = \App\Models\Favorite::where('user_id', $user->id)
            ->join('books', 'favorites.book_id', '=', 'books.id')
            ->pluck('books.category_id')
            ->unique();

        $allInteractedCategories = $loanedCategoryIds->merge($favoriteCategoryIds)->unique();

        if ($allInteractedCategories->isEmpty()) {
            // Si no hay historial, recomendar los más populares
            return Book::withCount('loans')
                ->where('stock', '>', 0)
                ->orderBy('loans_count', 'desc')
                ->take(6)
                ->get()
                ->map(function ($book) {
                    if ($book->cover_image) {
                        $book->cover_image_url = asset('storage/' . $book->cover_image);
                    }
                    return $book;
                });
        }

        // Recomendar libros de esas categorías que NO haya leído/marcado
        $excludeBookIds = \App\Models\Loan::where('user_id', $user->id)
            ->join('book_units', 'loans.book_unit_id', '=', 'book_units.id')
            ->pluck('book_units.book_id')
            ->merge(\App\Models\Favorite::where('user_id', $user->id)->pluck('book_id'))
            ->unique();

        return Book::whereIn('category_id', $allInteractedCategories)
            ->whereNotIn('id', $excludeBookIds)
            ->where('stock', '>', 0)
            ->with(['author', 'category'])
            ->take(6)
            ->get()
            ->map(function ($book) {
                if ($book->cover_image) {
                    $book->cover_image_url = asset('storage/' . $book->cover_image);
                }
                return $book;
            });
    }
}
