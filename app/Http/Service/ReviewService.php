<?php

namespace App\Http\Service;

use App\Models\Review;
use App\Models\Book;

class ReviewService
{
    public function getReviewsByBook(Book $book)
    {
        return Review::with('user:id,name')->where('book_id', $book->id)->latest()->get();
    }

    public function createReview(array $data)
    {
        return Review::create($data);
    }
}
