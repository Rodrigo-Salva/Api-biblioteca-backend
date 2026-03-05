<?php

namespace App\Http\Service;

use Illuminate\Support\Facades\Http;

class ISBNMetadataService
{
    protected $baseUrl = 'https://www.googleapis.com/books/v1/volumes';

    /**
     * Fetch book metadata by ISBN
     */
    public function fetchByIsbn(string $isbn)
    {
        $response = Http::get($this->baseUrl, [
            'q' => 'isbn:' . $isbn,
        ]);

        if ($response->failed() || !isset($response->json()['items'][0])) {
            return null;
        }

        $info = $response->json()['items'][0]['volumeInfo'];

        return [
            'title' => $info['title'] ?? null,
            'authors' => $info['authors'] ?? [],
            'publisher' => $info['publisher'] ?? null,
            'year' => isset($info['publishedDate']) ? substr($info['publishedDate'], 0, 4) : null,
            'synopsis' => $info['description'] ?? null,
            'pages' => $info['pageCount'] ?? null,
            'cover_image' => $info['imageLinks']['thumbnail'] ?? null,
            'categories' => $info['categories'] ?? [],
        ];
    }
}
