<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Http\Service\ReservationService;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    protected $service;

    public function __construct(ReservationService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/reservations",
     *     summary="List user's reservations",
     *     tags={"Reservations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pendiente", "disponible", "cancelada", "expirada"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of reservations",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Reservation")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $reservations = $this->service->listByUser(
            auth()->user(),
            $request->get('status')
        );

        return response()->json($reservations);
    }

    /**
     * @OA\Post(
     *     path="/api/reservations",
     *     summary="Create a reservation",
     *     tags={"Reservations"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"book_id"},
     *             @OA\Property(property="book_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reservation created",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reserva creada exitosamente"),
     *             @OA\Property(property="reservation", ref="#/components/schemas/Reservation"),
     *             @OA\Property(property="queue_position", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(response=409, description="Conflict - Already has reservation"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreReservationRequest $request)
    {
        try {
            $reservation = $this->service->create(auth()->user(), $request->book_id);
            $position = $this->service->getQueuePosition($reservation);

            return response()->json([
                'message' => 'Reserva creada exitosamente',
                'reservation' => $reservation,
                'queue_position' => $position,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/reservations/{reservation}",
     *     summary="Get reservation details",
     *     tags={"Reservations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="reservation",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reservation details",
     *         @OA\JsonContent(
     *             @OA\Property(property="reservation", ref="#/components/schemas/Reservation"),
     *             @OA\Property(property="queue_position", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(Reservation $reservation)
    {
        // Verificar autorización
        if ($reservation->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $position = $this->service->getQueuePosition($reservation);

        return response()->json([
            'reservation' => $reservation->load(['user', 'book']),
            'queue_position' => $position,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/reservations/{reservation}",
     *     summary="Cancel a reservation",
     *     tags={"Reservations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="reservation",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reservation cancelled",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reserva cancelada exitosamente")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(Reservation $reservation)
    {
        // Verificar autorización
        if ($reservation->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        try {
            $this->service->cancel($reservation);
            return response()->json(['message' => 'Reserva cancelada exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/reservations",
     *     summary="List all reservations (admin only)",
     *     tags={"Reservations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pendiente", "disponible", "cancelada", "expirada"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of all reservations"
     *     ),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function adminIndex(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $reservations = $this->service->listAll($request->get('status'));
        return response()->json($reservations);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/reservations/{reservation}",
     *     summary="Update reservation status (admin only)",
     *     tags={"Reservations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="reservation",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"pendiente", "disponible", "cancelada", "expirada"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reservation updated"
     *     ),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function update(UpdateReservationRequest $request, Reservation $reservation)
    {
        $reservation = $this->service->updateStatus($reservation, $request->status);
        return response()->json($reservation);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/reservations/{reservation}",
     *     summary="Delete reservation (admin only)",
     *     tags={"Reservations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="reservation",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Reservation deleted"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function adminDestroy(Reservation $reservation)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $this->service->delete($reservation);
        return response()->noContent();
    }

    /**
     * @OA\Get(
     *     path="/api/admin/reservations/statistics",
     *     summary="Get reservations statistics (admin only)",
     *     tags={"Reservations"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Reservations statistics",
     *         @OA\JsonContent(
     *             @OA\Property(property="total_reservations", type="integer", example=50),
     *             @OA\Property(property="pending_reservations", type="integer", example=15),
     *             @OA\Property(property="available_reservations", type="integer", example=5),
     *             @OA\Property(property="expired_reservations", type="integer", example=10),
     *             @OA\Property(property="cancelled_reservations", type="integer", example=20)
     *         )
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