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
use App\Http\Controllers\FineController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReservationController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Rutas Públicas (Sin autenticación)
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/books/available', [BookController::class, 'available']);

/*
|--------------------------------------------------------------------------
| Rutas Protegidas (Requieren autenticación)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    
    // Usuario autenticado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Cerrar sesión
    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | Libros - Lectura (Todos los usuarios autenticados)
    |--------------------------------------------------------------------------
    */
    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/{book}', [BookController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | Categorías - Lectura (Todos los usuarios autenticados)
    |--------------------------------------------------------------------------
    */
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | Autores - Lectura (Todos los usuarios autenticados)
    |--------------------------------------------------------------------------
    */
    Route::get('/authors', [AuthorController::class, 'index']);
    Route::get('/authors/{author}', [AuthorController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | Préstamos - Usuario
    |--------------------------------------------------------------------------
    */
    Route::get('/loans', [LoanController::class, 'index']);
    Route::get('/loans/{loan}', [LoanController::class, 'show']);
    Route::post('/loans', [LoanController::class, 'store']);

    /*
    |--------------------------------------------------------------------------
    | Favoritos - Usuario
    |--------------------------------------------------------------------------
    */
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{book_id}', [FavoriteController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | Reseñas - Todos los usuarios autenticados
    |--------------------------------------------------------------------------
    */
    Route::get('/books/{book}/reviews', [ReviewController::class, 'index']);
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
    Route::get('/books/{book}/average-rating', [ReviewController::class, 'averageRating']);

    /*
    |--------------------------------------------------------------------------
    | Multas - Usuario
    |--------------------------------------------------------------------------
    */
    Route::get('/fines', [FineController::class, 'index']);
    Route::get('/fines/summary', [FineController::class, 'summary']);
    Route::post('/fines/{fine}/pay', [FineController::class, 'pay']);

    /*
    |--------------------------------------------------------------------------
    | Notificaciones - Usuario
    |--------------------------------------------------------------------------
    */
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | Reservas - Usuario
    |--------------------------------------------------------------------------
    */
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | Rutas de Administrador
    |--------------------------------------------------------------------------
    */
    Route::middleware(IsAdmin::class)->group(function () {
        
        // Usuarios (Admin)
        Route::apiResource('users', UserController::class)->except(['store']);

        // Libros (Admin)
        Route::post('/books', [BookController::class, 'store']);
        Route::put('/books/{book}', [BookController::class, 'update']);
        Route::delete('/books/{book}', [BookController::class, 'destroy']);

        // Categorías (Admin)
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        // Autores (Admin)
        Route::post('/authors', [AuthorController::class, 'store']);
        Route::put('/authors/{author}', [AuthorController::class, 'update']);
        Route::delete('/authors/{author}', [AuthorController::class, 'destroy']);

        // Préstamos (Admin)
        Route::put('/loans/{loan}', [LoanController::class, 'update']);
        Route::delete('/loans/{loan}', [LoanController::class, 'destroy']);
        Route::post('/loans/{id}/return', [LoanController::class, 'markAsReturned']);
        Route::get('/loans/overdue', [LoanController::class, 'overdue']);
        Route::get('/loans/near-due', [LoanController::class, 'nearDue']);

        // Multas - CRUD Completo (Admin)
        Route::post('/admin/fines', [FineController::class, 'store']);
        Route::get('/admin/fines', [FineController::class, 'adminIndex']);
        Route::put('/admin/fines/{fine}', [FineController::class, 'update']);
        Route::delete('/admin/fines/{fine}', [FineController::class, 'destroy']);
        Route::get('/admin/fines/statistics', [FineController::class, 'statistics']);

        // Reservas - Admin
        Route::get('/admin/reservations', [ReservationController::class, 'adminIndex']);
        Route::put('/admin/reservations/{reservation}', [ReservationController::class, 'update']);
        Route::delete('/admin/reservations/{reservation}', [ReservationController::class, 'adminDestroy']);
        Route::get('/admin/reservations/statistics', [ReservationController::class, 'statistics']);
    });
});