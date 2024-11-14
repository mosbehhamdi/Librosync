<?php

return [
    'auth' => [
        'failed' => 'These credentials do not match our records.',
        'password' => 'The provided password is incorrect.',
        'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    ],
    'validation' => [
        'required' => 'The :attribute field is required.',
        'email' => 'The :attribute must be a valid email address.',
        'min' => [
            'string' => 'The :attribute must be at least :min characters.',
        ],
        'confirmed' => 'The :attribute confirmation does not match.',
    ],
    'books' => [
        'created' => 'Book created successfully.',
        'updated' => 'Book updated successfully.',
        'deleted' => 'Book deleted successfully.',
        'not_found' => 'Book not found.',
    ],
    'reservations' => [
        'created' => 'Reservation created successfully.',
        'cancelled' => 'Reservation cancelled successfully.',
        'accepted' => 'Reservation accepted successfully.',
        'delivered' => 'Book delivered successfully.',
        'returned' => 'Book returned successfully.',
        'not_found' => 'Reservation not found.',
    ]
];
