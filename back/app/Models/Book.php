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
} 