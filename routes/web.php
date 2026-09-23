<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/php-info', function () {
    phpinfo();
});

// Email Preview & Inspection Studio
Route::prefix('email-previews')->name('email-previews.')->group(function () {
    Route::get('/', [\App\Http\Controllers\EmailPreviewController::class, 'index'])->name('index');
    Route::match(['get', 'post'], '/render', [\App\Http\Controllers\EmailPreviewController::class, 'renderView'])->name('render');
    Route::post('/update/{id}', [\App\Http\Controllers\EmailPreviewController::class, 'updateTemplate'])->name('update');
    Route::post('/send-test', [\App\Http\Controllers\EmailPreviewController::class, 'sendTest'])->name('send-test');
});