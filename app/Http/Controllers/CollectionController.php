<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CollectionController extends Controller
{
    public function index()
    {
        return Auth::user()->collections()->with('books.author')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        return Auth::user()->collections()->create($validated);
    }

    public function show(Collection $collection)
    {
        if ($collection->user_id !== Auth::id() && !$collection->is_public) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return $collection->load('books.author', 'books.category');
    }

    public function update(Request $request, Collection $collection)
    {
        if ($collection->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        $collection->update($validated);
        return $collection;
    }

    public function destroy(Collection $collection)
    {
        if ($collection->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $collection->delete();
        return response()->noContent();
    }

    public function addBook(Request $request, Collection $collection)
    {
        if ($collection->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate(['book_id' => 'required|exists:books,id']);
        
        $collection->books()->syncWithoutDetaching([$request->book_id]);
        
        return response()->json(['message' => 'Libro añadido a la colección']);
    }

    public function removeBook(Request $request, Collection $collection, $bookId)
    {
        if ($collection->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $collection->books()->detach($bookId);
        
        return response()->json(['message' => 'Libro eliminado de la colección']);
    }
}
