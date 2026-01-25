<?php

namespace App\Http\Controllers;

use App\Models\Fine;
use App\Http\Requests\StoreFineRequest;
use App\Http\Requests\UpdateFineRequest;
use App\Http\Service\FineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FineController extends Controller
{
    protected $service;

    public function __construct(FineService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/fines",
     *     summary="List authenticated user's fines",
     *     tags={"Fines"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pendiente", "pagada"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of fines"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $fines = $this->service->listByUser(
            auth()->user(),
            $request->get('status')
        );

        return response()->json($fines);
    }

    /**
     * @OA\Get(
     *     path="/api/fines/summary",
     *     summary="Get fines summary for authenticated user",
     *     tags={"Fines"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Fines summary"
     *     )
     * )
     */
    public function summary()
    {
        return response()->json(
            $this->service->getUserSummary(auth()->user())
        );
    }

    /**
     * @OA\Post(
     *     path="/api/fines/{fine}/pay",
     *     summary="Pay a fine",
     *     tags={"Fines"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="fine",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fine paid successfully"
     *     ),
     *     @OA\Response(response=400, description="Fine already paid"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function pay(Fine $fine)
    {
        if (!$this->service->canPay($fine, auth()->id())) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        try {
            $fine = $this->service->pay($fine);
            
            return response()->json([
                'message' => 'Multa pagada exitosamente',
                'fine' => $fine
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/fines",
     *     summary="Create a fine manually (admin only)",
     *     tags={"Fines"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "loan_id", "amount", "days_overdue"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="loan_id", type="integer", example=1),
     *             @OA\Property(property="amount", type="number", format="float", example=10.50),
     *             @OA\Property(property="days_overdue", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Fine created",
     *         @OA\JsonContent(ref="#/components/schemas/Fine")
     *     ),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreFineRequest $request)
    {
        $fine = $this->service->create($request->validated());
        return response()->json($fine, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/fines",
     *     summary="List all fines (admin only)",
     *     tags={"Fines"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pendiente", "pagada"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of all fines"
     *     ),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function adminIndex(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $fines = $this->service->listAll($request->get('status'));
        return response()->json($fines);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/fines/{fine}",
     *     summary="Update a fine (admin only)",
     *     tags={"Fines"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="fine",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="amount", type="number", format="float", example=15.00),
     *             @OA\Property(property="days_overdue", type="integer", example=7),
     *             @OA\Property(property="status", type="string", enum={"pendiente", "pagada"}, example="pendiente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fine updated",
     *         @OA\JsonContent(ref="#/components/schemas/Fine")
     *     ),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Fine not found")
     * )
     */
    public function update(UpdateFineRequest $request, Fine $fine)
    {
        $fine = $this->service->update($fine, $request->validated());
        return response()->json($fine);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/fines/{fine}",
     *     summary="Delete a fine (admin only)",
     *     tags={"Fines"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="fine",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Fine deleted"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Fine not found")
     * )
     */
    public function destroy(Fine $fine)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $this->service->delete($fine);
        return response()->noContent();
    }

    /**
     * @OA\Get(
     *     path="/api/admin/fines/statistics",
     *     summary="Get fines statistics (admin only)",
     *     tags={"Fines"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Fines statistics"
     *     ),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function statistics()
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json($this->service->getStatistics());
    }
}