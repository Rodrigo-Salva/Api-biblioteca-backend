<?php

namespace App\Http\Service;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookService
{
    /**
     * Listar libros con filtros avanzados y paginación
     */
    public function list($filters = [])
    {
        // Support both Request object and array
        $request = null;
        if ($filters instanceof Request) {
            $request = $filters;
            $filters = $request->all();
        }

        if (isset($filters['all']) && $filters['all'] === true) {
            return Book::with(['author', 'category'])->get()->transform(function ($book) {
                if ($book->cover_image) {
                    $book->cover_image_url = asset('storage/' . $book->cover_image);
                }
                return $book;
            });
        }

        $query = Book::query()->with(['author', 'category']);

        // Búsqueda por texto (título, ISBN o autor)
        $search = $filters['search'] ?? $filters['q'] ?? null;
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('isbn', 'like', "%{$search}%")
                  ->orWhereHas('author', function($authorQuery) use ($search) {
                      $authorQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filtrar por autor
        if (isset($filters['author_id'])) {
            if (is_string($filters['author_id']) && str_contains($filters['author_id'], ',')) {
                $query->whereIn('author_id', explode(',', $filters['author_id']));
            } else {
                $query->where('author_id', $filters['author_id']);
            }
        }

        // Filtrar por categoría
        if (isset($filters['category_id'])) {
            if (is_string($filters['category_id']) && str_contains($filters['category_id'], ',')) {
                $query->whereIn('category_id', explode(',', $filters['category_id']));
            } else {
                $query->where('category_id', $filters['category_id']);
            }
        }

        // Filtrar por año exacto
        if (isset($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        // Filtrar por rango de años
        if (isset($filters['year_from'])) {
            $query->where('year', '>=', $filters['year_from']);
        }
        if (isset($filters['year_to'])) {
            $query->where('year', '<=', $filters['year_to']);
        }

        // Filtrar solo libros disponibles
        if (isset($filters['available']) && ($filters['available'] === 'true' || $filters['available'] === true)) {
            $query->where('stock', '>', 0);
        }

        // Ordenamiento
        $sortField = $filters['sort'] ?? 'title';
        $sortOrder = $filters['order'] ?? 'asc';
        
        $allowedSorts = ['title', 'year', 'pages', 'created_at', 'stock'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        }

        // Paginación
        $perPage = $filters['per_page'] ?? 15;
        $books = $query->paginate($perPage);

        // Agregar cover_image_url a cada libro
        $books->getCollection()->transform(function ($book) {
            if ($book->cover_image) {
                $book->cover_image_url = asset('storage/' . $book->cover_image);
            }
            return $book;
        });

        return $books;
    }

    public function available(array $filters = [])
    {
        $filters['available'] = true;
        return $this->list($filters);
    }

    public function create(array $data, $coverImageFile = null, $digitalFile = null): Book
    {
        if ($coverImageFile) {
            $data['cover_image'] = $coverImageFile->store('covers', 'public');
        }

        if ($digitalFile) {
            $data['digital_file_path'] = $digitalFile->store('books/digital', 'public');
            $data['is_digital'] = true;
        }

        return Book::create($data);
    }

    public function update(Book $book, array $data, $coverImageFile = null, $digitalFile = null): Book
    {
        if ($coverImageFile) {
            if ($book->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }

            $data['cover_image'] = $coverImageFile->store('covers', 'public');
        }

        if ($digitalFile) {
            if ($book->digital_file_path) {
                Storage::disk('public')->delete($book->digital_file_path);
            }

            $data['digital_file_path'] = $digitalFile->store('books/digital', 'public');
            $data['is_digital'] = true;
        }

        $book->update($data);
        return $book;
    }

    public function delete(Book $book): void
    {
        if ($book->cover_image) {
            Storage::disk('public')->delete($book->cover_image);
        }

        if ($book->digital_file_path) {
            Storage::disk('public')->delete($book->digital_file_path);
        }

        $book->delete();
    }

    public function get(Book $book): Book
    {
        $book->load(['author', 'category', 'reviews.user']);
        if ($book->cover_image) {
            $book->cover_image_url = asset('storage/' . $book->cover_image);
        }
        return $book;
    }

    public function getRecommendations(\App\Models\User $user): \Illuminate\Support\Collection
    {
        // Obtener categorías de los libros que el usuario ha pedido prestados o marcado como favoritos
        $loanedCategoryIds = \App\Models\Loan::where('user_id', $user->id)
            ->join('books', 'loans.book_id', '=', 'books.id')
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
            ->pluck('loans.book_id')
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

    /**
     * Decrementar stock de un libro
     */
    public function decrementStock(Book $book): bool
    {
        if ($book->stock > 0) {
            $book->disminuirStock();
            return true;
        }
        return false;
    }

    /**
     * Incrementar stock de un libro
     */
    public function incrementStock(Book $book): bool
    {
        $book->incrementarStock();
        return true;
    }
}
