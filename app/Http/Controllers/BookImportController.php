<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\LogHelper;

class BookImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));

        if (count($data) < 2) {
            return response()->json(['message' => 'El archivo está vacío o no tiene el formato correcto.'], 400);
        }

        $headers = array_shift($data); // Quitar cabecera
        $count = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($data as $index => $row) {
                if (count($row) < 5) continue; // Mínimo de columnas

                // Estructura esperada: titulo, isbn, año, editorial, stock, author_name, category_name
                $title = $row[0];
                $isbn = $row[1];
                $year = $row[2];
                $publisher = $row[3];
                $stock = $row[4];
                $authorName = $row[5] ?? 'Anónimo';
                $categoryName = $row[6] ?? 'General';

                // Buscar o crear autor
                $author = Author::firstOrCreate(['name' => $authorName]);
                
                // Buscar o crear categoría
                $category = Category::firstOrCreate(['name' => $categoryName]);

                Book::create([
                    'title' => $title,
                    'isbn' => $isbn,
                    'year' => $year,
                    'publisher' => $publisher,
                    'stock' => $stock,
                    'author_id' => $author->id,
                    'category_id' => $category->id,
                ]);

                $count++;
            }
            
            DB::commit();
            LogHelper::log('Importado', 'Libros', null, "Importación masiva: $count libros.");
            
            return response()->json([
                'message' => "Se han importado $count libros con éxito.",
                'count' => $count
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error durante la importación: ' . $e->getMessage()
            ], 500);
        }
    }
}
