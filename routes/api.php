<?php

use App\Http\Middleware\IsAdmin;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BookUnitController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BookQRController;
use App\Http\Controllers\BookImportController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\HelpRequestController;
use Illuminate\Http\Request;
// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Ruta pública para listar libros con stock disponible
Route::get('/books/available', [BookController::class, 'available']);
Route::get('/banners', [BannerController::class, 'index']);

// Rutas protegidas por autenticacion Sanctum
Route::middleware('auth:sanctum')->group(function () {


    Route::get('/user', function (Request $request) {
        return $request->user();
    });


    // Ruta para que solo el admin pueda administrart los usuarios
    Route::apiResource('users', UserController::class)->except(['store']);

    //para el cierre de session
    Route::post('/logout', [AuthController::class, 'logout']);

    //Para que usuario pueda hacer el prestamos
    Route::post('/loans', [LoanController::class, 'store']);

    Route::post('/loans/{loan}/mark-returned', [LoanController::class, 'markAsReturned']);


    // Lectura permitida para todos los usuarios autenticados
    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/recommendations', [BookController::class, 'recommendations']);
    Route::get('/books/{book}', [BookController::class, 'show']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);

    Route::get('/authors', [AuthorController::class, 'index']);
    Route::get('/authors/{author}', [AuthorController::class, 'show']);

    Route::get('/loans', [LoanController::class, 'index']);
    Route::get('/loans/{loan}', [LoanController::class, 'show']);

    // Rutas para Reseñas
    Route::get('/books/{book}/reviews', [ReviewController::class, 'index']);
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store']);

    // Rutas para Reservas
    Route::get('/my-reservations', [ReservationController::class, 'myReservations']);
    Route::post('/reservations', [ReservationController::class, 'store']);

    // Perfil y Notificaciones
    Route::get('/profile', [ProfileController::class, 'getProfileStats']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

    // Multas
    Route::post('/loans/{loan}/pay', [LoanController::class, 'payFine']);

    // Colecciones
    Route::apiResource('collections', CollectionController::class);
    Route::post('/collections/{collection}/books', [CollectionController::class, 'addBook']);
    Route::delete('/collections/{collection}/books/{bookId}', [CollectionController::class, 'removeBook']);

    // Help Requests
    Route::get('/help-requests', [HelpRequestController::class, 'index']);
    Route::post('/help-requests', [HelpRequestController::class, 'store']);
    Route::get('/help-requests/{helpRequest}', [HelpRequestController::class, 'show']);
    Route::delete('/help-requests/{helpRequest}', [HelpRequestController::class, 'destroy']);
    // The update route is moved inside the admin middleware group as per the instruction "with update being admin-only"

    // Rutas para administrador
    Route::middleware(IsAdmin::class)->group(function () {
        Route::get('/admin/stats', [AdminDashboardController::class, 'stats']);
        
        // Reportes
        Route::get('/admin/reports/loans', [ReportController::class, 'exportLoans']);
        Route::get('/admin/reports/inventory', [ReportController::class, 'exportInventory']);
        Route::get('/admin/reports/fines', [ReportController::class, 'exportFines']);
        // Books
        Route::post('/books', [BookController::class, 'store']);
        Route::put('/books/{book}', [BookController::class, 'update']);
        Route::delete('/books/{book}', [BookController::class, 'destroy']);

        // Ruta para decrementar stock de un libro
        Route::post('/books/{book}/decrement-stock', [BookController::class, 'decrementStock']);

        // Categories
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        // Authors
        Route::post('/authors', [AuthorController::class, 'store']);
        Route::put('/authors/{author}', [AuthorController::class, 'update']);
        Route::delete('/authors/{author}', [AuthorController::class, 'destroy']);

        // Loans
        Route::put('/loans/{loan}', [LoanController::class, 'update']);
        Route::delete('/loans/{loan}', [LoanController::class, 'destroy']);

        // Book Units (Ejemplares)
        Route::get('/books/{book}/units', [BookUnitController::class, 'index']);
        Route::post('/books/{book}/units', [BookUnitController::class, 'store']);
        Route::get('/units/{unit}', [BookUnitController::class, 'show']);
        Route::delete('/units/{unit}', [BookUnitController::class, 'destroy']);

        // Auditoría
        Route::get('/activity-logs', [ActivityLogController::class, 'index']);

        // QR Codes
        Route::get('/books/{book}/qr', [BookQRController::class, 'generate']);

        // Bulk Import
        Route::post('/admin/books/import', [BookImportController::class, 'import']);

        // Banners
        Route::get('/admin/banners', [BannerController::class, 'all']);
        Route::post('/banners', [BannerController::class, 'store']);
        Route::put('/banners/{banner}', [BannerController::class, 'update']);
        // Help Requests (Admin)
        Route::patch('/help-requests/{helpRequest}', [HelpRequestController::class, 'update']);
    });
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{book_id}', [FavoriteController::class, 'destroy']);
});



Route::middleware(['auth:sanctum', 'is.admin'])->get('/admin-test', function () {
    return response()->json(['ok' => true]);
});
