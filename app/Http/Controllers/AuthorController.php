<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Http\Service\AuthorService as ServiceAuthorService;
use App\Http\Services\AuthorService;
use Illuminate\Support\Facades\Auth;

class AuthorController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/authors",
     *     summary="Get list of all authors",
     *     tags={"Authors"},
     *     @OA\Response(
     *         response=200,
     *         description="List of authors",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Author"))
     *     )
     * )
     */
    public function index(Request $request)
    {
        if ($request->has('all')) {
            return Author::all();
        }
        return Author::paginate(10);
    }

    /**
     * @OA\Post(
     *     path="/api/authors",
     *     summary="Create a new author",
     *     tags={"Authors"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="country", type="string", nullable=true),
     *             @OA\Property(property="bio", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Author created",
     *         @OA\JsonContent(ref="#/components/schemas/Author")
     *     ),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function store(StoreAuthorRequest $request, ServiceAuthorService $service)
    {
        $this->authorizeAdmin();
        return $service->create($request->validated());
    }

    /**
     * @OA\Get(
     *     path="/api/authors/{author}",
     *     summary="Get author by ID",
     *     tags={"Authors"},
     *     @OA\Parameter(
     *         name="author",
     *         in="path",
     *         description="Author ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Author details",
     *         @OA\JsonContent(ref="#/components/schemas/Author")
     *     ),
     *     @OA\Response(response=404, description="Author not found")
     * )
     */
    public function show(Author $author)
    {
        return $author;
    }

    /**
     * @OA\Put(
     *     path="/api/authors/{author}",
     *     summary="Update an existing author",
     *     tags={"Authors"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="author",
     *         in="path",
     *         description="Author ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="country", type="string", nullable=true),
     *             @OA\Property(property="bio", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Author updated",
     *         @OA\JsonContent(ref="#/components/schemas/Author")
     *     ),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Author not found")
     * )
     */
    public function update(UpdateAuthorRequest $request, Author $author, ServiceAuthorService $service)
    {
        $this->authorizeAdmin();
        return $service->update($author, $request->validated());
    }

    /**
     * @OA\Delete(
     *     path="/api/authors/{author}",
     *     summary="Delete an author",
     *     tags={"Authors"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="author",
     *         in="path",
     *         description="Author ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Author deleted"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Author not found")
     * )
     */
    public function destroy(Author $author, ServiceAuthorService $service)
    {
        $this->authorizeAdmin();
        $service->delete($author);
        return response()->noContent();
    }
    private function authorizeAdmin(): void
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized');
        }
    }
}
