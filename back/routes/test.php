<?php

use Illuminate\Support\Facades\Route;

Route::get('/debug', function() {
    return [
        'message' => 'Routes are loading',
        'api_file' => file_exists(base_path('routes/api.php')),
        'api_contents' => file_get_contents(base_path('routes/api.php')),
    ];
});
