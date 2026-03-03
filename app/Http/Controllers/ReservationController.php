<?php

namespace App\Http\Controllers;

use App\Http\Service\ReservationService;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
    protected $service;

    public function __construct(ReservationService $service)
    {
        $this->service = $service;
    }

    public function myReservations()
    {
        return response()->json($this->service->getReservationsByUser(Auth::user()));
    }

    public function store(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
        ]);

        try {
            $reservation = $this->service->createReservation(Auth::user(), $request->book_id);
            return response()->json($reservation, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}
