<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group(['middleware' => ['apiJwt']], function () {
    Route::resource('contacts', App\Http\Controllers\API\ContactController::class)
        ->except(['create', 'edit', 'index']);
});

Route::resource('contacts', App\Http\Controllers\API\ContactController::class)
    ->only(['index']);
