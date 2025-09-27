<?php

use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\ProdutoController;
use App\Http\Controllers\Api\TokenAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->name('v1.')->group(function () {
    Route::post('auth/login', [TokenAuthController::class, 'login'])->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [TokenAuthController::class, 'me'])->name('auth.me');

        Route::post('auth/logout', [TokenAuthController::class, 'logout'])->name('auth.logout');

        Route::apiResource('produtos', ProdutoController::class);

        Route::apiResource('logs', LogController::class)->only(['index']);
    });
});
