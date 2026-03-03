<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Biblioteca API - Enterprise Edition",
 *     description="Documentación completa y profesional de la API para el sistema de gestión de biblioteca. Incluye módulos de administración avanzada, trazabilidad (Audit Logs), reportes dinámicos y herramientas operativas (QR, Bulk Import).",
 *     @OA\Contact(email="soporte@bibliotecaapi.com")
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Servidor de API Principal"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Token de autenticación Sanctum"
 * )
 *
 * @OA\Tag(name="Auth", description="Autenticación y Sesión")
 * @OA\Tag(name="Admin | Dashboard", description="Estadísticas avanzadas y analíticas")
 * @OA\Tag(name="Admin | Books", description="Gestión de inventario de libros")
 * @OA\Tag(name="Admin | Authors", description="Gestión de autores")
 * @OA\Tag(name="Admin | Categories", description="Gestión de categorías")
 * @OA\Tag(name="Admin | Loans", description="Control de préstamos y devoluciones")
 * @OA\Tag(name="Admin | Users", description="Gestión de usuarios y roles")
 * @OA\Tag(name="Admin | Audit Logs", description="Trazabilidad y auditoría de acciones")
 * @OA\Tag(name="Admin | Tools", description="Herramientas operativas (QR, Import)")
 * @OA\Tag(name="Public | Books", description="Catálogo público para lectores")
 * @OA\Tag(name="Reservations", description="Reservas")
 * @OA\Tag(name="Reviews", description="Reseñas")
 * @OA\Tag(name="Fines", description="Multas")
 *
 * @OA\Schema(
 *     schema="Book",
 *     type="object",
 *     required={"id", "title", "isbn", "year", "author_id", "category_id"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Cien años de soledad"),
 *     @OA\Property(property="isbn", type="string", example="9780307474728"),
 *     @OA\Property(property="year", type="integer", example=1967),
 *     @OA\Property(property="author_id", type="integer", example=10),
 *     @OA\Property(property="category_id", type="integer", example=5),
 *     @OA\Property(property="cover_image_url", type="string", format="uri", example="http://api.test/storage/covers/1.jpg"),
 *     @OA\Property(property="stock", type="integer", example=15),
 *     @OA\Property(property="synopsis", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="Author",
 *     type="object",
 *     required={"id", "name"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Gabriel García Márquez"),
 *     @OA\Property(property="country", type="string", example="Colombia"),
 *     @OA\Property(property="bio", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     required={"id", "name"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Realismo Mágico")
 * )
 *
 * @OA\Schema(
 *     schema="Loan",
 *     type="object",
 *     required={"id", "user_id", "book_id", "status"},
 *     @OA\Property(property="id", type="integer", example=5),
 *     @OA\Property(property="user_id", type="integer"),
 *     @OA\Property(property="book_id", type="integer"),
 *     @OA\Property(property="status", type="string", enum={"pendiente", "aprobado", "devuelto"}),
 *     @OA\Property(property="loan_date", type="string", format="date"),
 *     @OA\Property(property="due_date", type="string", format="date"),
 *     @OA\Property(property="return_date", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ActivityLog",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="action", type="string", example="Creado"),
 *     @OA\Property(property="model_type", type="string", example="Libro"),
 *     @OA\Property(property="description", type="string", example="Título: El Quijote"),
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Admin Principal"),
 *     @OA\Property(property="email", type="string", example="admin@biblioteca.com"),
 *     @OA\Property(property="role", type="string", example="admin")
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
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
