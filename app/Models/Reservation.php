<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'status',
        'reserved_at',
        'notified_at',
        'expires_at',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'notified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pendiente');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'disponible');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expirada');
    }

    // Métodos auxiliares
    public function isPending()
    {
        return $this->status === 'pendiente';
    }

    public function isAvailable()
    {
        return $this->status === 'disponible';
    }

    public function isExpired()
    {
        return $this->status === 'expirada' || 
               ($this->expires_at && now()->greaterThan($this->expires_at));
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelada']);
    }

    public function markAsAvailable()
    {
        $this->update([
            'status' => 'disponible',
            'notified_at' => now(),
            'expires_at' => now()->addDays(2),
        ]);
    }

    public function markAsExpired()
    {
        $this->update(['status' => 'expirada']);
    }
}
