<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

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
 *
 * --- SCHEMAS ---
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
 */
class SwaggerAnnotations extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/dashboard/stats",
     *     summary="Estadísticas globales para el Dashboard",
     *     description="Retorna indicadores clave (KPIs), tendencias mensuales y distribución de categorías.",
     *     tags={"Admin | Dashboard"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Datos estadísticos cargados con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="cards", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="monthlyTrends", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="categoryDistribution", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function dashboardStats() {}

    /**
     * @OA\Get(
     *     path="/api/activity-logs",
     *     summary="Listado de logs de auditoría",
     *     description="Retorna el registro histórico de acciones realizadas por los administradores (trazabilidad).",
     *     tags={"Admin | Audit Logs"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de logs",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ActivityLog"))
     *         )
     *     )
     * )
     */
    public function auditLogs() {}

    /**
     * @OA\Get(
     *     path="/api/books/{id}/qr",
     *     summary="Generar código QR para un libro",
     *     description="Genera una etiqueta QR en formato SVG con la información del ejemplar.",
     *     tags={"Admin | Tools"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Imagen SVG del código QR")
     * )
     */
    public function generateQR() {}

    /**
     * @OA\Post(
     *     path="/api/admin/books/import",
     *     summary="Importación masiva de libros vía CSV",
     *     description="Permite cargar múltiples libros, creando autores y categorías automáticamente si no existen.",
     *     tags={"Admin | Tools"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(@OA\Property(property="file", type="string", format="binary"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Importación exitosa")
     * )
     */
    public function importBooks() {}

    /**
     * @OA\Get(
     *     path="/api/admin/reports/loans",
     *     summary="Exportar reporte de préstamos",
     *     description="Genera documentos en formato PDF o Excel con el historial de préstamos.",
     *     tags={"Admin | Tools"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="format", in="query", required=true, @OA\Schema(type="string", enum={"pdf", "excel"})),
     *     @OA\Response(response=200, description="Archivo descargable")
     * )
     */
    public function exportReports() {}
}
