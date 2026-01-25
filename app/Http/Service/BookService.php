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
    public function list(Request $request = null)
    {
        $query = Book::query()->with(['author', 'category']);

        // Si se pasa un Request, aplicar filtros
        if ($request) {
            // Búsqueda por texto (título, ISBN o autor)
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('isbn', 'like', "%{$search}%")
                      ->orWhereHas('author', function($authorQuery) use ($search) {
                          $authorQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Filtrar por autor
            if ($request->has('author_id')) {
                $query->where('author_id', $request->author_id);
            }

            // Filtrar por categoría
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Filtrar por año exacto
            if ($request->has('year')) {
                $query->where('year', $request->year);
            }

            // Filtrar por rango de años
            if ($request->has('year_from')) {
                $query->where('year', '>=', $request->year_from);
            }
            if ($request->has('year_to')) {
                $query->where('year', '<=', $request->year_to);
            }

            // Filtrar solo libros disponibles
            if ($request->has('available') && $request->available === 'true') {
                $query->where('stock', '>', 0);
            }

            // Ordenamiento
            $sortField = $request->get('sort', 'title');
            $sortOrder = $request->get('order', 'asc');
            
            $allowedSorts = ['title', 'year', 'pages', 'created_at', 'stock'];
            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortOrder);
            }

            // Paginación
            $perPage = $request->get('per_page', 15);
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

        // Si no hay Request, devolver todos los libros (comportamiento original)
        return $query->get()->map(function ($book) {
            if ($book->cover_image) {
                $book->cover_image_url = asset('storage/' . $book->cover_image);
            }
            return $book;
        });
    }

    public function available(): \Illuminate\Support\Collection
    {
        return Book::where('stock', '>', 0)
            ->with(['author', 'category'])
            ->get()
            ->map(function ($book) {
                if ($book->cover_image) {
                    $book->cover_image_url = asset('storage/' . $book->cover_image);
                }
                return $book;
            });
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
        $book->load(['author', 'category', 'reviews.user']);
        if ($book->cover_image) {
            $book->cover_image_url = asset('storage/' . $book->cover_image);
        }
        return $book;
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