<?php

use Illuminate\Support\Facades\Route;

Route::get("/", function() {
    return "Web route is working";
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');
