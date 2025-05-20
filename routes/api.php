<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\AuthController; 
use App\Http\Controllers\BookmarkController; 
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
    Route::post('/login', [AuthController::class, 'login'])->name("login"); // Login User
    Route::get('/blogs', [BlogController::class, 'getAllPosts']); // View Blogs (Public)
    Route::post('/blogs', [BlogController::class, 'store']); // Create Blog

     Route::middleware('auth:sanctum')->group(function () {
        // Route::post('/blogs', [BlogController::class, 'store']); // Create Blog
        Route::put('/blogs/{id}', [BlogController::class, 'update']); // Update Blog
        Route::get('/user', [AuthController::class, 'user']); // Get Logged-in User
        Route::post('/logout', [AuthController::class, 'logout']); // Logout User
        Route::get('/blogs/filter', [BlogController::class, 'filter']);//Filter by 
        Route::get('/blogs/search', [BlogController::class, 'search']);//Common Search
        Route::put('/user/profile', [AuthController::class, 'updateProfile']);//update User profile
        Route::post('/blogs/{id}/bookmark', [BookmarkController::class, 'toggle']);//add/remove Bookmarks
        Route::get('/bookmarks', [BookmarkController::class, 'index']);//get all bookmarks
        Route::post('/blogs/{id}/approve', [BlogController::class, 'approve']); //Admin post approval
        Route::post('/blogs/{id}/reject', [BlogController::class, 'reject']); //Admin Post Rejection
        Route::get('/blogs/pending', [BlogController::class, 'getPending']); //Get pending posts for Admin
        Route::get('/admin/users', [AdminUserController::class, 'index']);         // Get all users
        Route::put('/admin/users/{id}/role', [AdminUserController::class, 'updateRole']); // Update user role
     });
