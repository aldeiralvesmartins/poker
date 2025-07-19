<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\GameController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Mesas
    Route::apiResource('tables', TableController::class);
    Route::post('/tables/{id}/join', [TableController::class, 'join']);
    Route::post('/tables/{id}/leave', [TableController::class, 'leave']);

    // Jogos
    Route::post('/tables/{id}/start', [GameController::class, 'start']);
    Route::post('/games/{id}/action', [GameController::class, 'action']);

    Route::post('/games/{id}/finish', [GameController::class, 'finishGame'])->middleware('auth:sanctum');
});
