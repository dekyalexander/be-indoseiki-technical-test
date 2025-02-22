<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BooksController;
use App\Http\Controllers\BorrowsController;
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

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refreshToken']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/books', [BooksController::class, 'index']);
    Route::post('/books', [BooksController::class, 'store']);
    Route::get('/books/{id}', [BooksController::class, 'show']);
    Route::put('/books/{id}', [BooksController::class, 'update']); 
    Route::delete('/books/{id}', [BooksController::class, 'destroy']);

    Route::get('/borrows', [BorrowsController::class, 'index']);
    Route::post('/borrows', [BorrowsController::class, 'store']);
    Route::put('/borrows/{id}', [BorrowsController::class, 'update']);
    Route::delete('/borrows/{id}', [BorrowsController::class, 'destroy']);
    Route::post('/borrows/{id}/return', [BorrowsController::class, 'returnBook']);
});
