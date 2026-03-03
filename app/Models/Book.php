<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Author;
use App\Models\Category;


/**
 * @mixin IdeHelperBook
 */
class Book extends Model
{

    use HasFactory;
    protected $fillable = ['title', 'isbn', 'year', 'author_id', 'category_id', 'synopsis', 'pages', 'publisher', 'stock', 'cover_image'];
    protected $appends = ['cover_image_url'];

    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function units()
    {
        return $this->hasMany(BookUnit::class);
    }

    public function disminuirStock()

    {
        if ($this->stock <= 0) {
            throw new \Exception('El libro no está disponible (sin stock)');
        }

        $this->stock -= 1;
        $this->save();
    }

    public function incrementarStock()
    {
        $this->stock += 1;
        $this->save();
    }

    public function favoredBy(){
        return $this->belongsToMany(\App\Models\User::class, 'favorites');
    }
    
    public function getCoverImageUrlAttribute()
    {
    return $this->cover_image ? asset('storage/' . $this->cover_image) : null; 
    }
}
