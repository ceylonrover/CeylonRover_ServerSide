<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\AuthController; 
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\BookmarkController; 
use App\Http\Controllers\LikeController;
use App\Http\Controllers\TravsnapController;
use App\Http\Controllers\ModeratorAssignmentController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\BlogModerationController;
use App\Http\Controllers\BlogHighlightController;
use App\Http\Controllers\TravsnapModerationController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/    Route::post('/register', [AuthController::class, 'register']); // Register User
    Route::post('/login', [AuthController::class, 'login'])->name("login"); // Login User
    Route::post('/admin/login', [AdminAuthController::class, 'login'])->name("admin.login"); // Admin Login
    
    // Email Verification Routes (public)
    Route::post('/email/verify/send-otp', [App\Http\Controllers\EmailVerificationController::class, 'sendOTP']);
    Route::post('/email/verify/verify-otp', [App\Http\Controllers\EmailVerificationController::class, 'verifyOTP']);
    Route::post('/email/verify/resend-otp', [App\Http\Controllers\EmailVerificationController::class, 'resendOTP']);    Route::get('/blogs', [BlogController::class, 'getAllPosts']); // View Blogs (Public)
    Route::get('/blogs/{id}', [BlogController::class, 'show']); // View Single Blog (Public)
    Route::get('/travsnaps', [TravsnapController::class, 'getAllTravsnaps']); // View Travsnaps (Public)
    Route::get('/travsnaps/{id}', [TravsnapController::class, 'getById']); // View Single Travsnap (Public)
    Route::get('/travsnaps/featured', [TravsnapController::class, 'getFeatured']); // View Featured Travsnaps (Public)    Route::post('/blogs/filter', [BlogController::class, 'filter']); //Filter Blogs (Public) - POST to allow body parameters
    Route::post('/travsnaps/filter', [TravsnapController::class, 'filter']); //Filter Travsnaps (Public) - POST to allow body parameters
    Route::get('/blogs/debug/location-samples', [BlogController::class, 'getLocationSamples']); // Debug endpoint for location data
    Route::post('/blogs/filter', [BlogController::class, 'filter']); //Filter Blogs (Public) - POST to allow body parameters
     
    Route::middleware('auth:sanctum')->group(function () {        Route::post('/blogs', [BlogController::class, 'store']); // Create Blog
        Route::put('/blogs/{id}', [BlogController::class, 'update']); // Update Blog
        Route::get('/user', [AuthController::class, 'user']); // Get Logged-in User        Route::get('/email/verify/status', [AuthController::class, 'checkEmailVerification']); // Check email verification status
        Route::post('/email/verify', [AuthController::class, 'verifyEmail']); // Verify email with OTP (authenticated users)
        Route::post('/logout', [AuthController::class, 'logout']); // Logout User
        Route::get('/blogs/search', [BlogController::class, 'search']);//Common Search
        Route::put('/user/profile', [AuthController::class, 'updateProfile']);//update User profile
          // New profile endpoints
        Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'getProfile']); // Get user profile
        Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'updateProfile']); // Update user profile
        Route::post('/profile/image', [App\Http\Controllers\ProfileController::class, 'uploadProfileImage']); // Upload profile image
        
        //Likes and Bookmarks
        Route::post('/blogs/{id}/like', [LikeController::class, 'toggle']);
        Route::get('/blogs/{id}/likes', [LikeController::class, 'getLikes']);

        Route::post('/blogs/{id}/bookmark', [BookmarkController::class, 'toggle']);
        Route::get('/blogs/{id}/bookmarks', [BookmarkController::class, 'getBookmarks']);
        Route::get('/user/bookmarks', [BookmarkController::class, 'userBookmarks']);
        
        Route::post('/moderator/assign', [ModeratorAssignmentController::class, 'assign']);
        Route::get('/moderator/assignments/{moderatorId?}', [ModeratorAssignmentController::class, 'getModeratorAssignments']);        Route::get('/moderator/unassigned-content', [ModeratorAssignmentController::class, 'getUnassignedContent']);
        Route::get('/moderator/available', [ModeratorAssignmentController::class, 'getAvailableModerators']);

        // Admin blog approval routes
        Route::middleware([\App\Http\Middleware\AdminAccessMiddleware::class])->group(function () {            // New blog moderation routes
            Route::get('/moderation/blogs/pending', [BlogModerationController::class, 'getPendingBlogs']);
            Route::get('/moderation/blogs/approved', [BlogModerationController::class, 'getApprovedBlogs']);
            Route::get('/moderation/blogs/rejected', [BlogModerationController::class, 'getRejectedBlogs']);
            Route::get('/moderation/blogs/pending/{id}', [BlogModerationController::class, 'getPendingBlogDetails']);
            Route::get('/moderation/blogs/approved/{id}', [BlogModerationController::class, 'getApprovedBlogDetails']);
            Route::get('/moderation/blogs/rejected/{id}', [BlogModerationController::class, 'getRejectedBlogDetails']);
            Route::post('/moderation/blogs/{id}/approve', [BlogModerationController::class, 'approveBlog']);
            Route::post('/moderation/blogs/{id}/reject', [BlogModerationController::class, 'rejectBlog']);
            
            // New travsnap moderation routes
            Route::get('/moderation/travsnaps/pending', [TravsnapModerationController::class, 'getPendingTravsnaps']);
            Route::get('/moderation/travsnaps/approved', [TravsnapModerationController::class, 'getApprovedTravsnaps']);
            Route::get('/moderation/travsnaps/rejected', [TravsnapModerationController::class, 'getRejectedTravsnaps']);
            Route::get('/moderation/travsnaps/pending/{id}', [TravsnapModerationController::class, 'getPendingTravsnapDetails']);
            Route::get('/moderation/travsnaps/approved/{id}', [TravsnapModerationController::class, 'getApprovedTravsnapDetails']);
            Route::get('/moderation/travsnaps/rejected/{id}', [TravsnapModerationController::class, 'getRejectedTravsnapDetails']);
            Route::post('/moderation/travsnaps/{id}/approve', [TravsnapModerationController::class, 'approveTravsnap']);
            Route::post('/moderation/travsnaps/{id}/reject', [TravsnapModerationController::class, 'rejectTravsnap']);
            
            Route::get('/admin/users', [AdminUserController::class, 'index']);         // Get all users
            Route::put('/admin/users/{id}/role', [AdminUserController::class, 'updateRole']); // Update user role
            
            // Feature toggle for travsnaps
            Route::post('/travsnaps/{id}/feature', [TravsnapController::class, 'toggleFeatured']); // Toggle Featured (Admin)

            //Trending posts [post Highlights]
            Route::get('/highlights', [BlogHighlightController::class, 'index']);
            Route::post('/highlights', [BlogHighlightController::class, 'storeOrUpdate']);
            Route::delete('/highlights/{id}', [BlogHighlightController::class, 'destroy']);
        
        });
        

        // Travsnap routes (authenticated)        
        // Route::post('/travsnaps', [TravsnapController::class, 'store']); // Create Travsnap
        Route::put('/travsnaps/{id}', [TravsnapController::class, 'update']); // Update Travsnap
        Route::delete('/travsnaps/{id}', [TravsnapController::class, 'destroy']); // Delete Travsnap
        Route::get('/travsnaps/user/{userId}', [TravsnapController::class, 'getUserTravsnaps']); // Get User's Travsnaps
         
     });
    // Admin authentication routes
    Route::middleware(['auth:sanctum', \App\Http\Middleware\AdminAccessMiddleware::class])->group(function () {
        Route::post('/admin/logout', [AdminAuthController::class, 'logout']);
        Route::get('/admin/user', [AdminAuthController::class, 'user']);
    });
