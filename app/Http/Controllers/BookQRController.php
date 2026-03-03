<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BookQRController extends Controller
{
    public function generate($id)
    {
        $book = Book::findOrFail($id);
        
        // El contenido del código QR será una URL ficticia o el ISBN del libro
        // Para este proyecto, usaremos un link al detalle del libro (aunque sea interno)
        $url = config('app.url') . "/books/" . $book->id;
        
        $qr = QrCode::size(300)
            ->format('svg')
            ->margin(1)
            ->color(16, 39, 76) // #10274C (Color primario de la app)
            ->generate($url);

        return response($qr)->header('Content-Type', 'image/svg+xml');
    }
}
