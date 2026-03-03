<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\BookUnit;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\LogHelper;

class ReportController extends Controller
{
    private function generateExcelHtml($title, $headers, $data)
    {
        $html = '
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta http-equiv="Content-type" content="text/html;charset=utf-8" />
            <style>
                .table-reports { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
                .table-reports th { background-color: #1e293b; color: #ffffff; padding: 12px; border: 1px solid #334155; text-align: left; font-size: 14px; }
                .table-reports td { padding: 10px; border: 1px solid #e2e8f0; font-size: 13px; }
                .table-reports tr:nth-child(even) { background-color: #f8fafc; }
                .title-header { font-size: 22px; font-weight: bold; color: #0f172a; margin-bottom: 20px; text-align: center; }
                .status-active { color: #059669; font-weight: bold; }
                .status-pending { color: #dc2626; font-weight: bold; }
                .money { color: #0891b2; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="title-header">' . $title . '</div>
            <table class="table-reports">
                <thead>
                    <tr>';
        
        foreach ($headers as $header) {
            $html .= '<th>' . $header . '</th>';
        }

        $html .= '</tr>
                </thead>
                <tbody>';

        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $styleClass = '';
                if (str_contains($cell, '$')) $styleClass = ' class="money"';
                if ($cell === 'Aprobado' || $cell === 'Saldada' || $cell === 'Disponible') $styleClass = ' class="status-active"';
                if ($cell === 'Pendiente' || $cell === 'En préstamo' || $cell === 'Pendiente cobro') $styleClass = ' class="status-pending"';
                
                $html .= '<td' . $styleClass . '>' . $cell . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '
                </tbody>
            </table>
        </body>
        </html>';

        return $html;
    }

    private function generateResponse($title, $headers, $data, $filename, $request)
    {
        LogHelper::log('Exportado', 'Reporte', null, "Reporte: $title");

        if ($request->query('format') === 'pdf') {
            $pdf = Pdf::loadView('reports.generic', [
                'title' => $title,
                'headers' => $headers,
                'data' => $data
            ]);
            return $pdf->download($filename . '.pdf');
        }

        $html = $this->generateExcelHtml($title, $headers, $data);
        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename=' . $filename . '.xls');
    }

    public function exportLoans()
    {
        $loans = Loan::with(['user', 'unit.book'])
            ->orderBy('loan_date', 'desc')
            ->get();
        
        $data = [];
        foreach ($loans as $loan) {
            $data[] = [
                $loan->id,
                $loan->unit->book->title ?? 'N/A',
                $loan->user->name,
                $loan->loan_date,
                $loan->return_date ?? 'En préstamo',
                ucfirst($loan->status),
                '$' . number_format($loan->fine_amount ?? 0, 2)
            ];
        }

        return $this->generateResponse('Reporte Maestro de Préstamos', ['ID', 'Libro', 'Usuario', 'Fecha Préstamo', 'Fecha Dev.', 'Estado', 'Multa'], $data, 'reporte_prestamos', request());
    }

    public function exportInventory()
    {
        $units = BookUnit::with('book.category')
            ->join('books', 'book_units.book_id', '=', 'books.id')
            ->orderBy('books.title', 'asc')
            ->select('book_units.*')
            ->get();

        $data = [];
        foreach ($units as $unit) {
            $data[] = [
                $unit->id,
                $unit->book->title ?? 'N/A',
                $unit->book->category->name ?? 'N/A',
                $unit->aisle ?? 'No asignado',
                $unit->shelf ?? 'No asignado',
                $unit->position ?? 'No asignada',
                ucfirst($unit->status)
            ];
        }

        return $this->generateResponse('Inventario Físico de Unidades', ['ID Unidad', 'Título', 'Categoría', 'Pasillo', 'Estante', 'Posición', 'Estado'], $data, 'reporte_inventario', request());
    }

    public function exportFines()
    {
        $loans = Loan::whereNotNull('fine_amount')
            ->with('user')
            ->orderBy('fine_amount', 'desc')
            ->get();

        $data = [];
        foreach ($loans as $loan) {
            $data[] = [
                $loan->id,
                $loan->user->name,
                $loan->user->email,
                '$' . number_format($loan->fine_amount, 2),
                $loan->status === 'returned' ? 'Saldada' : 'Pendiente cobro'
            ];
        }

        return $this->generateResponse('Control de Multas y Deudas', ['ID Préstamo', 'Usuario', 'Email', 'Monto', 'Estado Pago'], $data, 'reporte_multas', request());
    }
}
