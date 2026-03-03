<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Loan;
use App\Models\Review;
use App\Models\Reservation;

class ProfileController extends Controller
{
    public function getProfileStats(Request $request)
    {
        $user = $request->user();

        $stats = [
            'total_loans' => Loan::where('user_id', $user->id)->count(),
            'active_loans' => Loan::where('user_id', $user->id)->whereIn('status', ['pendiente', 'aprobado'])->whereNull('return_date')->count(),
            'total_reviews' => Review::where('user_id', $user->id)->count(),
            'active_reservations' => Reservation::where('user_id', $user->id)->where('status', 'pendiente')->count(),
            'pending_fines' => Loan::where('user_id', $user->id)->where('fine_amount', '>', 0)->where('is_paid', false)->sum('fine_amount'),
        ];

        $recent_activity = Loan::with('book')
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
            ],
            'stats' => $stats,
            'recent_activity' => $recent_activity,
        ]);
    }
}
