<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = [
        'user_id',
        'book_id',
        'status',
        'queue_position',
        'expires_at',
        'delivered_at',
        'returned_at',
        'due_date'
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_READY = 'ready';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_CANCELLED = 'cancelled';

    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_READY,
        self::STATUS_ACCEPTED,
        self::STATUS_DELIVERED
    ];

    public const PAST_STATUSES = [
        self::STATUS_RETURNED,
        self::STATUS_CANCELLED
    ];

    protected $casts = [
        'status_history' => 'array',
        'expires_at' => 'datetime',
        'delivered_at' => 'datetime',
        'returned_at' => 'datetime',
        'due_date' => 'datetime'
    ];

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_DELIVERED
            && $this->due_date
            && $this->due_date < now();
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
