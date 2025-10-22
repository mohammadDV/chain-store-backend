<?php

use Application\Api\Brand\Controllers\BrandController;
use Application\Api\File\Controllers\FileController;
use Application\Api\Notification\Controllers\NotificationController;
use Application\Api\Post\Controllers\PostController;
use Application\Api\Product\Controllers\CategoryController;
use Application\Api\Product\Controllers\ProductController;
use Application\Api\Review\Controllers\ReviewController;
use Application\Api\Ticket\Controllers\TicketController;
use Application\Api\Ticket\Controllers\TicketSubjectController;
use Application\Api\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Category
Route::get('/active-categories/{brand}', [CategoryController::class, 'activeProductCategories'])->name('active-product-categories');
Route::get('/all-categories/{brand}', [CategoryController::class, 'allCategories'])->name('all-categories');
Route::get('/brands/{brand}/categories/{category}/children', [CategoryController::class, 'getCategoryChildren'])->name('category-children');
Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('category.show');


// Products
Route::prefix('products')->group(function () {
    Route::post('search', [ProductController::class, 'search']);
    Route::post('search-suggestions', [ProductController::class, 'searchSuggestions']);
    Route::get('{product}/similar', [ProductController::class, 'similarProducts'])->name('product.similar');
    Route::post('featured', [ProductController::class, 'getFeaturedProducts'])->name('product.featured');
    Route::get('{product}', [ProductController::class, 'show']);
    Route::get('{product}/reviews', [ReviewController::class, 'getReviewsPerProduct'])->name('product.reviews.get');
});


// user info
Route::get('/user-info/{user}', [UserController::class, 'getUserInfo'])->name('user.show');

// ticket subjects
Route::get('/active-subjects', [TicketSubjectController::class, 'activeSubjects'])->name('active-subjects');


// Brand
Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{brand}', [BrandController::class, 'show'])->name('brands.show');

Route::get('/posts', [PostController::class, 'getPosts'])->name('site.posts.index');
Route::get('/posts/popular', [PostController::class, 'getPopularPosts'])->name('site.posts.popular');
Route::get('/posts/latest', [PostController::class, 'getLatestPosts'])->name('site.posts.latest');
Route::get('/post/{post}', [PostController::class, 'getPostInfo'])->name('site.post.info');

Route::middleware(['auth:sanctum', 'auth', 'throttle:200,1'])->prefix('profile')->name('profile.')->group(function() {

    // product
    Route::get('products/{product}/favorite', [ProductController::class, 'favorite'])->name('products.favorite');
    Route::get('products/favorite', [ProductController::class, 'getFavoriteProducts'])->name('products.favorite.index');
    // Route::get('my-products', [ProductController::class, 'index'])->name('products.index');

    // review
    Route::get('my-reviews', [ReviewController::class, 'myReviews'])->name('reviews.index');
    Route::post('reviews/{product}', [ReviewController::class, 'store'])->name('reviews.store');
    Route::patch('reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::get('reviews/{review}/change-status', [ReviewController::class, 'changeStatus'])->name('reviews.change-status');


    Route::get('/check-verification', [UserController::class, 'checkVerification'])->name('user.check.verification');

    // // update user
    Route::get('/my-info', [UserController::class, 'show'])->name('user.show');
    Route::patch('/users', [UserController::class, 'update'])->name('user.update');
    Route::patch('/users/change-password', [UserController::class, 'changePassword'])->name('user.change-password');


    Route::resource('notifications', NotificationController::class);
    Route::get('/notifications-unread', [NotificationController::class, 'unread'])->name('unread-notifications');
    Route::get('/notifications-read-all', [NotificationController::class, 'readAll'])->name('read-all-notifications');

    Route::resource('tickets', TicketController::class);
    Route::post('tickets/{ticket}/message', [TicketController::class, 'storeMessage'])->name('profile.ticket.message.store');
    Route::post('/ticket-status/{ticket}', [TicketController::class, 'closeTicket'])->name('profile.ticket.close-ticket');
    Route::get('ticket-subjects', [TicketSubjectController::class, 'activeSubjects'])->name('profile.ticket.active-subjects');

    // // activity count
    Route::get('/dashboard-info', [UserController::class, 'getDashboardInfo'])->name('profile.dashboard.info');

});

// upload files
Route::middleware(['auth:sanctum', 'auth', 'throttle:10,1'])->group(function() {
    Route::post('/upload-image', [FileController::class, 'uploadImage'])->name('site.upload-image');
    Route::post('/upload-video', [FileController::class, 'uploadVideo'])->name('site.upload-video');
    Route::post('/upload-file', [FileController::class, 'uploadFile'])->name('site.upload-file');
});
