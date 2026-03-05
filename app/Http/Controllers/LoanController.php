<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreLoanRequest;
use App\Http\Requests\UpdateLoanRequest;
use App\Http\Service\LoanService;
use App\Helpers\LogHelper;

class LoanController extends Controller
{
    protected $service;

    public function __construct(LoanService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/loans",
     *     summary="List all loans (admin) or user's own loans",
     *     tags={"Loans"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Loan"))
     *     )
     * )
     */
    public function index()
    {
        $user = Auth::user();
        return $this->service->listLoansForUser($user);
    }

    /**
     * @OA\Post(
     *     path="/api/loans",
     *     summary="Solicitar un préstamo (usuario autenticado)",
     *     tags={"Loans"},
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
     *         description="Préstamo aprobado automáticamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Préstamo aprobado automáticamente."),
     *             @OA\Property(property="loan", ref="#/components/schemas/Loan")
     *         )
     *     ),
     *     @OA\Response(response=403, description="No puedes solicitar el préstamo"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function store(StoreLoanRequest $request)
    {
        $user = Auth::user();

        try {
            $loan = $this->service->createLoan($user, $request->validated());

            LogHelper::log('Creado', 'Préstamo', $loan->id, "Usuario: {$loan->user->name}, Libro: {$loan->book->title}");

            return response()->json([
                'message' => 'Préstamo aprobado automáticamente.',
                'loan' => $loan
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/loans/{loan}",
     *     summary="Get a specific loan",
     *     tags={"Loans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="loan",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Loan found", @OA\JsonContent(ref="#/components/schemas/Loan")),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(Loan $loan)
    {
        $user = Auth::user();

        if ($user->role !== 'admin' && $loan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $loan->load(['user', 'book']);
    }

    /**
     * @OA\Put(
     *     path="/api/loans/{loan}",
     *     summary="Actualizar préstamo (solo admin). Si se establece return_date por primera vez, se devuelve el libro y se aumenta el stock.",
     *     tags={"Loans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="loan",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer", example=3),
     *             @OA\Property(property="book_id", type="integer", example=11),
     *             @OA\Property(property="loan_date", type="string", format="date", nullable=true, example="2025-06-01"),
     *             @OA\Property(property="return_date", type="string", format="date", nullable=true, example="2025-06-15"),
     *             @OA\Property(property="status", type="string", example="aprobado")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Préstamo actualizado", @OA\JsonContent(ref="#/components/schemas/Loan")),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function update(UpdateLoanRequest $request, Loan $loan)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $wasReturned = $loan->return_date !== null;

        $loan->update($request->validated());

        if (!$wasReturned && $request->filled('return_date')) {
            $loan->book->incrementarStock();
        }

        LogHelper::log('Actualizado', 'Préstamo', $loan->id, "Estado: {$loan->status}");

        return $loan;
    }

    /**
     * @OA\Delete(
     *     path="/api/loans/{loan}",
     *     summary="Delete loan (admin only)",
     *     tags={"Loans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="loan",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Loan deleted"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(Loan $loan)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        LogHelper::log('Eliminado', 'Préstamo', $loan->id, "ID Préstamo: {$loan->id}");
        $loan->delete();
        return response()->noContent();
    }

    /**
     * @OA\Post(
     *     path="/api/loans/{id}/return",
     *     summary="Marcar préstamo como devuelto (solo admin)",
     *     tags={"Loans"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del préstamo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Préstamo marcado como devuelto",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Préstamo marcado como devuelto."),
     *             @OA\Property(property="fine_generated", type="boolean", example=true),
     *             @OA\Property(property="fine_amount", type="number", format="float", example=10.00)
     *         )
     *     ),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Préstamo no encontrado o ya devuelto")
     * )
     */
    public function markAsReturned($id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $loan = Loan::find($id);
        if (!$loan) {
            return response()->json(['message' => 'Préstamo no encontrado'], 404);
        }

        try {
            $loan = $this->service->markAsReturned($loan);
            
            // Verificar si se generó una multa
            $fine = $loan->fine;
            
            LogHelper::log('Devuelto', 'Préstamo', $loan->id, "Libro devuelto por admin");

            return response()->json([
                'message' => 'Préstamo marcado como devuelto.',
                'fine_generated' => $fine !== null,
                'fine_amount' => $fine ? $fine->amount : 0
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function payFine(Loan $loan)
    {
        try {
            $updatedLoan = $this->service->payFine($loan);
            return response()->json($updatedLoan);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function renew(Loan $loan)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $loan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $updatedLoan = $this->service->renew($loan);
            LogHelper::log('Renovado', 'Préstamo', $loan->id, "Nueva fecha: {$updatedLoan->due_date}");
            return response()->json($updatedLoan);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/loans/overdue",
     *     summary="Get overdue loans (admin only)",
     *     tags={"Loans"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of overdue loans",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Loan")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function overdue()
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json($this->service->getOverdueLoans());
    }

    /**
     * @OA\Get(
     *     path="/api/loans/near-due",
     *     summary="Get loans near due date (admin only)",
     *     tags={"Loans"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of loans near due date (within 3 days)",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Loan")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function nearDue()
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json($this->service->getLoansNearDue());
    }
}
