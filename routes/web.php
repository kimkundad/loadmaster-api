<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/images/{file}', function ($file) {
    $url = Storage::disk('do_spaces')->temporaryUrl(
        $file,
        now()->addMinutes(5)
    );
    if ($url) {
        return Redirect::to($url);
    }
    return abort(404);
})->where('file', '.+');


Route::get('/send-test-email', function () {
    $emailData = [
        'title' => 'Test Email',
        'body' => 'This is a test email sent using Gmail SMTP in Laravel.',
    ];

    // Use the PDFMail mailable as an example (optional)
    Mail::to('kim.kundad@gmail.com')->send(new \App\Mail\PDFMail($emailData, ''));

    return 'Test email sent!';
});
