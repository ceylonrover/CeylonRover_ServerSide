<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\AuthController; 
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

Route::post('/register', [AuthController::class, 'register']); // Register User
Route::post('/login', [AuthController::class, 'login']); // Login User
Route::get('/blogs', [BlogController::class, 'getAllPosts']); // View Blogs (Public)

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/blogs', [BlogController::class, 'store']); // Create Blog
    Route::put('/blogs/{id}', [BlogController::class, 'update']); // Update Blog
    Route::get('/user', [AuthController::class, 'user']); // Get Logged-in User
    Route::post('/logout', [AuthController::class, 'logout']); // Logout User
});