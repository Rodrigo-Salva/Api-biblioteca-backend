<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use App\Models\Loan;
use App\Models\Review;
use App\Models\Reservation;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function stats()
    {
        $totalBooks = Book::count();
        $totalUsers = User::count();
        $activeLoans = Loan::whereIn('status', ['pendiente', 'aprobado'])->whereNull('return_date')->count();
        $noStock = Book::where('stock', '<=', 0)->count();

        // Libros más populares
        $popularBooks = Book::withCount('loans')
            ->orderBy('loans_count', 'desc')
            ->take(5)
            ->get();

        // Reseñas recientes
        $recentReviews = Review::with(['user', 'book'])
            ->latest()
            ->take(5)
            ->get();

        // Reservas pendientes
        $pendingReservations = Reservation::with(['user', 'book'])
            ->where('status', 'pendiente')
            ->latest()
            ->take(5)
            ->get();

        // Préstamos vs Devoluciones por mes (últimos 6 meses)
        $monthlyTrends = collect(range(5, 0))->map(function ($i) {
            $date = now()->subMonths($i);
            $monthName = $date->format('M');
            $month = $date->month;
            $year = $date->year;

            return [
                'name' => $monthName,
                'loans' => Loan::whereMonth('loan_date', $month)->whereYear('loan_date', $year)->count(),
                'returns' => Loan::whereMonth('return_date', $month)->whereYear('return_date', $year)->count(),
            ];
        });

        // Distribución por categoría
        $categoryDistribution = DB::table('categories')
            ->join('books', 'categories.id', '=', 'books.category_id')
            ->select('categories.name', DB::raw('count(books.id) as value'))
            ->groupBy('categories.name')
            ->get();

        // Estadísticas de multas
        $finesStats = [
            'total_pending' => Loan::where('is_paid', false)->sum('fine_amount'),
            'total_collected' => Loan::where('is_paid', true)->sum('fine_amount'),
        ];

        // Top Usuarios (Más préstamos)
        $topUsers = User::withCount('loans')
            ->orderBy('loans_count', 'desc')
            ->take(5)
            ->get();

        // Actividad Reciente (Audit Logs)
        $recentActivity = ActivityLog::with('user')
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'cards' => [
                ['label' => 'Libros', 'value' => $totalBooks],
                ['label' => 'Usuarios', 'value' => $totalUsers],
                ['label' => 'Préstamos activos', 'value' => $activeLoans],
                ['label' => 'Sin Stock', 'value' => $noStock],
            ],
            'popularBooks' => $popularBooks,
            'recentReviews' => $recentReviews,
            'pendingReservations' => $pendingReservations,
            'monthlyTrends' => $monthlyTrends,
            'categoryDistribution' => $categoryDistribution,
            'finesStats' => $finesStats,
            'topUsers' => $topUsers,
            'recentActivity' => $recentActivity,
        ]);
    }
}
