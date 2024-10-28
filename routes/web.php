<?php

use App\Mail\TestMail;
use Illuminate\Support\Facades\Mail;

Route::get('/test-mail', function () {
    try {
        Mail::to('mesbahhamdidsi@gmail.com')->send(new TestMail());
        return 'Test email sent successfully!';
    } catch (\Exception $e) {
        return 'Error sending email: ' . $e->getMessage();
    }
});
