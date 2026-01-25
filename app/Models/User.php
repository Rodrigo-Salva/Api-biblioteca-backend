<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Book;
use App\Models\Loan;

/**
 * @mixin IdeHelperUser
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Libros favoritos del usuario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Book>
     */
    public function favoriteBooks()
    {
        return $this->belongsToMany(Book::class, 'favorites', 'user_id', 'book_id');
    }

    public function fines()
    {
        return $this->hasMany(Fine::class);
    }

    public function hasPendingFines()
    {
        return $this->fines()->where('status', 'pendiente')->exists();
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function hasActiveReservation($bookId)
    {
        return $this->reservations()
            ->where('book_id', $bookId)
            ->whereIn('status', ['pendiente', 'disponible'])
            ->exists();
    }
}
