<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="API Biblioteca Virtual",
 *     version="1.0.0",
 *     description="API RESTful completa para gestión de biblioteca virtual",
 *     @OA\Contact(
 *         email="soporte@biblioteca.com",
 *         name="Soporte Técnico"
 *     )
 * )
 *
 * @OA\Server(url="http://127.0.0.1:8000", description="Servidor Local")
 * @OA\Server(url="http://localhost:8000", description="Servidor Alternativo")
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Token de autenticación Sanctum"
 * )
 *
 * @OA\Tag(name="Authentication", description="Autenticación")
 * @OA\Tag(name="Books", description="Libros")
 * @OA\Tag(name="Categories", description="Categorías")
 * @OA\Tag(name="Authors", description="Autores")
 * @OA\Tag(name="Users", description="Usuarios")
 * @OA\Tag(name="Loans", description="Préstamos")
 * @OA\Tag(name="Favorites", description="Favoritos")
 * @OA\Tag(name="Reviews", description="Reseñas")
 * @OA\Tag(name="Fines", description="Multas")
 * @OA\Tag(name="Notifications", description="Notificaciones")
 * @OA\Tag(name="Reservations", description="Reservas")
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Juan Pérez"),
 *     @OA\Property(property="email", type="string", example="juan@example.com"),
 *     @OA\Property(property="role", type="string", example="user"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Author",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Robert C. Martin"),
 *     @OA\Property(property="country", type="string", example="USA"),
 *     @OA\Property(property="bio", type="string", example="Autor de Clean Code"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Programación"),
 *     @OA\Property(property="description", type="string", example="Libros de programación"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Book",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Clean Code"),
 *     @OA\Property(property="author_id", type="integer", example=1),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="isbn", type="string", example="978-0132350884"),
 *     @OA\Property(property="published_year", type="integer", example=2008),
 *     @OA\Property(property="stock", type="integer", example=5),
 *     @OA\Property(property="description", type="string", example="A Handbook of Agile Software Craftsmanship"),
 *     @OA\Property(property="cover_image", type="string", example="https://example.com/cover.jpg"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Loan",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="book_id", type="integer", example=1),
 *     @OA\Property(property="loan_date", type="string", format="date", example="2026-01-25"),
 *     @OA\Property(property="due_date", type="string", format="date", example="2026-02-09"),
 *     @OA\Property(property="return_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="status", type="string", example="aprobado"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Review",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="book_id", type="integer", example=1),
 *     @OA\Property(property="rating", type="integer", example=5),
 *     @OA\Property(property="comment", type="string", example="Excelente libro"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Fine",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="loan_id", type="integer", example=1),
 *     @OA\Property(property="amount", type="number", format="float", example=10.00),
 *     @OA\Property(property="days_overdue", type="integer", example=5),
 *     @OA\Property(property="status", type="string", example="pendiente"),
 *     @OA\Property(property="paid_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Reservation",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="book_id", type="integer", example=1),
 *     @OA\Property(property="status", type="string", example="pendiente"),
 *     @OA\Property(property="reserved_at", type="string", format="date-time"),
 *     @OA\Property(property="notified_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
