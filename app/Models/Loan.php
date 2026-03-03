<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User; 
use App\Models\Book; 

/**
 * @mixin IdeHelperLoan
 */
class Loan extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'book_id', 'book_unit_id', 'loan_date', 'due_date', 'return_date', 'status', 'fine_amount', 'is_paid'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function book() {
        return $this->belongsTo(Book::class);
    }

    public function unit() {
        return $this->belongsTo(BookUnit::class, 'book_unit_id');
    }

}
