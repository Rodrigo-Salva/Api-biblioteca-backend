<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Service\BookService as ServiceBookService;
use App\Helpers\LogHelper;

class BookController extends Controller
{
    protected $bookService;

    public function __construct(ServiceBookService $bookService)
    {
        $this->bookService = $bookService;
    }

    /**
     * @OA\Get(
     *     path="/api/books",
     *     summary="List all books",
     *     tags={"Books"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Book"))
     *     )
     * )
     */
    public function index(Request $request)
    {
        return $this->bookService->list($request->all());
    }

    /**
     * @OA\Post(
     *     path="/api/books",
     *     summary="Create a new book",
     *     tags={"Books"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "isbn", "year", "author_id", "category_id"},
     *             @OA\Property(property="title", type="string", example="El señor de los anillos"),
     *             @OA\Property(property="isbn", type="string", example="9788478884453"),
     *             @OA\Property(property="year", type="integer", example=1954),
     *             @OA\Property(property="author_id", type="integer", example=2),
     *             @OA\Property(property="category_id", type="integer", example=3),
     *             @OA\Property(property="cover_image", type="string", format="binary", description="Cover image file"),
     *             @OA\Property(property="synopsis", type="string", example="Una historia fantástica..."),
     *             @OA\Property(property="pages", type="integer", example=423),
     *             @OA\Property(property="publisher", type="string", example="Editorial XYZ"),
     *             @OA\Property(property="stock", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Book created", @OA\JsonContent(ref="#/components/schemas/Book")),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreBookRequest $request)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $book = $this->bookService->create($request->validated(), $request->file('cover_image'));
        LogHelper::log('Creado', 'Libro', $book->id, "Título: {$book->title}");
        return response()->json($book, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/books/{book}",
     *     summary="Get a book by ID",
     *     tags={"Books"},
     *     @OA\Parameter(
     *         name="book",
     *         in="path",
     *         description="Book ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation", @OA\JsonContent(ref="#/components/schemas/Book")),
     *     @OA\Response(response=404, description="Book not found")
     * )
     */
    public function show(Book $book)
    {
        return $this->bookService->get($book);
    }

    /**
     * @OA\Put(
     *     path="/api/books/{book}",
     *     summary="Update a book",
     *     tags={"Books"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="book",
     *         in="path",
     *         description="Book ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="El señor de los anillos"),
     *             @OA\Property(property="isbn", type="string", example="9788478884453"),
     *             @OA\Property(property="year", type="integer", example=1954),
     *             @OA\Property(property="author_id", type="integer", example=2),
     *             @OA\Property(property="category_id", type="integer", example=3),
     *             @OA\Property(property="cover_image", type="string", format="binary", description="Cover image file"),
     *             @OA\Property(property="synopsis", type="string", example="Una historia fantástica..."),
     *             @OA\Property(property="pages", type="integer", example=423),
     *             @OA\Property(property="publisher", type="string", example="Editorial XYZ"),
     *             @OA\Property(property="stock", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Book updated", @OA\JsonContent(ref="#/components/schemas/Book")),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Book not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateBookRequest $request, Book $book)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $book = $this->bookService->update($book, $request->validated(), $request->file('cover_image'));
        LogHelper::log('Actualizado', 'Libro', $book->id, "Título: {$book->title}");
        return response()->json($book);
    }

    /**
     * @OA\Delete(
     *     path="/api/books/{book}",
     *     summary="Delete a book",
     *     tags={"Books"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="book",
     *         in="path",
     *         description="Book ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Book deleted"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Book not found")
     * )
     */
    public function destroy(Book $book)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->bookService->delete($book);
        LogHelper::log('Eliminado', 'Libro', $book->id, "Título: {$book->title}");
        return response()->json(null, 204);
    }

/**
 * @OA\Get(
 *     path="/api/books/available",
 *     summary="List books with stock available",
 *     tags={"Books"},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Book"))
 *     )
 * )
 */
    public function available(Request $request)
    {
        return response()->json($this->bookService->available($request->all()));
    }

    public function recommendations()
    {
        if (!\Illuminate\Support\Facades\Auth::check()) {
            return response()->json([]);
        }
        return response()->json($this->bookService->getRecommendations(\Illuminate\Support\Facades\Auth::user()));
    }
}
