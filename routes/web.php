<?php

use App\Http\Controllers\AfconMatchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api/afcon')->group(function () {
    Route::get('/quarter-finals', [AfconMatchController::class, 'quarterFinals']);
    Route::get('/matches/{matchId}', [AfconMatchController::class, 'show']);
});
