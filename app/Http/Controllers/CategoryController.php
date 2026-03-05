<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Service\CategoryService;
use App\Helpers\LogHelper;

class CategoryController extends Controller
{
    protected $service;

    public function __construct(CategoryService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/categories",
     *     summary="List all categories",
     *     tags={"Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Category"))
     *     )
     * )
     */
    public function index(Request $request)
    {
        return $this->service->list($request->has('all'));
    }

    /**
     * @OA\Post(
     *     path="/api/categories",
     *     summary="Create a new category",
     *     tags={"Categories"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Ficción"),
     *             @OA\Property(property="description", type="string", example="Libros de ficción y narrativa")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Category created", @OA\JsonContent(ref="#/components/schemas/Category")),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreCategoryRequest $request)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $category = $this->service->create($request->validated());
        LogHelper::log('Creado', 'Categoría', $category->id, "Nombre: {$category->name}");
        return $category;
    }

    /**
     * @OA\Get(
     *     path="/api/categories/{category}",
     *     summary="Get a category by ID",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         description="Category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation", @OA\JsonContent(ref="#/components/schemas/Category")),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function show(Category $category)
    {
        return $category;
    }

    /**
     * @OA\Put(
     *     path="/api/categories/{category}",
     *     summary="Update a category",
     *     tags={"Categories"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         description="Category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Ficción"),
     *             @OA\Property(property="description", type="string", example="Libros de ficción y narrativa")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Category updated", @OA\JsonContent(ref="#/components/schemas/Category")),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Category not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $category = $this->service->update($category, $request->validated());
        LogHelper::log('Actualizado', 'Categoría', $category->id, "Nombre: {$category->name}");
        return $category;
    }

    /**
     * @OA\Delete(
     *     path="/api/categories/{category}",
     *     summary="Delete a category",
     *     tags={"Categories"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         description="Category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Category deleted"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function destroy(Category $category)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        LogHelper::log('Eliminado', 'Categoría', $category->id, "Nombre: {$category->name}");
        $category->delete();
        return response()->noContent();
    }
}