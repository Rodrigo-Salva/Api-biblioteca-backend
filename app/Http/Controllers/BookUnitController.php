<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookUnit;
use App\Models\Reservation;
use App\Http\Service\NotificationService;
use Illuminate\Http\Request;

class BookUnitController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    private function fulfillReservations(Book $book)
    {
        $reservation = Reservation::where('book_id', $book->id)
            ->where('status', 'pendiente')
            ->oldest()
            ->first();

        if ($reservation) {
            $reservation->update(['status' => 'completada']);
            
            $this->notificationService->createNotification(
                $reservation->user_id,
                'reserva_disponible',
                '¡Libro disponible!',
                "El libro '{$book->title}' que reservaste ya tiene stock. ¡Ven por él!"
            );
        }
    }

    public function index(Book $book)
    {
        return response()->json($book->units()->get());
    }

    public function store(Request $request, Book $book)
    {
        $validated = $request->validate([
            'sku' => 'required|unique:book_units,sku',
            'condition' => 'required|in:nuevo,bueno,regular,malo',
            'status' => 'required|in:disponible,prestado,mantenimiento,perdido',
            'aisle' => 'nullable|string|max:50',
            'shelf' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:50',
        ]);

        $unit = $book->units()->create($validated);

        // Incrementar stock del libro automáticamente al agregar unidad disponible
        if ($unit->status === 'disponible') {
            $book->incrementarStock();
            $this->fulfillReservations($book);
        }

        return response()->json($unit, 201);
    }

    public function update(Request $request, BookUnit $unit)
    {
        $validated = $request->validate([
            'condition' => 'sometimes|in:nuevo,bueno,regular,malo',
            'status' => 'sometimes|in:disponible,prestado,mantenimiento,perdido',
            'aisle' => 'nullable|string|max:50',
            'shelf' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:50',
        ]);

        $oldStatus = $unit->status;
        $unit->update($validated);

        // Sincronizar stock si el estado cambia a/desde disponible
        if ($oldStatus !== 'disponible' && $unit->status === 'disponible') {
            $unit->book->incrementarStock();
            $this->fulfillReservations($unit->book);
        } elseif ($oldStatus === 'disponible' && $unit->status !== 'disponible') {
            $unit->book->disminuirStock();
        }

        return response()->json($unit);
    }

    public function destroy(BookUnit $unit)
    {
        if ($unit->status === 'disponible') {
            $unit->book->disminuirStock();
        }
        $unit->delete();
        return response()->noContent();
    }
}
