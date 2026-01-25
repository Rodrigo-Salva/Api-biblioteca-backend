<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Book;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Service\ReviewService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    protected $service;

    public function __construct(ReviewService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/books/{book}/reviews",
     *     summary="List reviews of a book",
     *     tags={"Reviews"},
     *     @OA\Parameter(
     *         name="book",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of reviews",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Review")
     *             ),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Book not found")
     * )
     */
    public function index(Book $book)
    {
        return response()->json($this->service->listByBook($book));
    }

    /**
     * @OA\Post(
     *     path="/api/books/{book}/reviews",
     *     summary="Create a review for a book",
     *     tags={"Reviews"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="book",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"rating"},
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5),
     *             @OA\Property(property="comment", type="string", example="Excelente libro, muy recomendado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Review created",
     *         @OA\JsonContent(ref="#/components/schemas/Review")
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=409, description="Review already exists"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreReviewRequest $request, Book $book)
    {
        try {
            $review = $this->service->create($book, auth()->id(), $request->validated());
            return response()->json($review, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/reviews/{review}",
     *     summary="Update own review",
     *     tags={"Reviews"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="review",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"rating"},
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=4),
     *             @OA\Property(property="comment", type="string", example="Actualicé mi opinión")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review updated",
     *         @OA\JsonContent(ref="#/components/schemas/Review")
     *     ),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Review not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateReviewRequest $request, Review $review)
    {
        if (!$this->service->canModify($review, auth()->id(), auth()->user()->role)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $review = $this->service->update($review, $request->validated());
        return response()->json($review);
    }

    /**
     * @OA\Delete(
     *     path="/api/reviews/{review}",
     *     summary="Delete own review",
     *     tags={"Reviews"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="review",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Review deleted"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Review not found")
     * )
     */
    public function destroy(Review $review)
    {
        if (!$this->service->canModify($review, auth()->id(), auth()->user()->role)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $this->service->delete($review);
        return response()->noContent();
    }

    /**
     * @OA\Get(
     *     path="/api/books/{book}/average-rating",
     *     summary="Get average rating of a book",
     *     tags={"Reviews"},
     *     @OA\Parameter(
     *         name="book",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Average rating",
     *         @OA\JsonContent(
     *             @OA\Property(property="average_rating", type="number", format="float", example=4.5),
     *             @OA\Property(property="total_reviews", type="integer", example=10)
     *         )
     *     )
     * )
     */
    public function averageRating(Book $book)
    {
        return response()->json($this->service->getAverageRating($book));
    }
}