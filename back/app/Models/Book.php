<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'authors',
        'copies_count',
        'available_copies',
        'parts_count',
        'publisher',
        'edition_number',
        'dewey_category',
        'dewey_subcategory',
        'price',
        'comments',
        'central_number',
        'local_number',
        'publication_date',
        'acquisition_date',
    ];

    protected $casts = [
        'authors' => 'array',
        'publication_date' => 'date',
        'acquisition_date' => 'date',
        'price' => 'decimal:2',
        'copies_count' => 'integer',
        'available_copies' => 'integer',
        'parts_count' => 'integer',
        'edition_number' => 'integer',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function activeReservations()
    {
        return $this->reservations()
            ->whereIn('status', ['pending', 'ready'])
            ->orderBy('queue_position');
    }

    public function getWaitingTimeAttribute()
    {
        $activeReservationsCount = $this->activeReservations()->count();
        if ($activeReservationsCount === 0 || $this->available_copies > 0) {
            return 0;
        }
        
        // Estimation basée sur le temps moyen d'emprunt (par exemple, 14 jours)
        return ceil($activeReservationsCount / $this->copies_count) * 14;
    }
} 