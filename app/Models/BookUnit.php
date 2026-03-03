<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'sku',
        'condition',
        'status',
        'aisle',
        'shelf',
        'position',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }
}
