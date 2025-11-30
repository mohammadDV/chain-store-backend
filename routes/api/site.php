<?php

use Application\Api\Brand\Controllers\BrandController;
use Application\Api\File\Controllers\FileController;
use Application\Api\Notification\Controllers\NotificationController;
use Application\Api\Product\Controllers\OrderController;
use Application\Api\Post\Controllers\PostController;
use Application\Api\Product\Controllers\CategoryController;
use Application\Api\Product\Controllers\ColorController;
use Application\Api\Product\Controllers\ProductController;
use Application\Api\Review\Controllers\ReviewController;
use Application\Api\Ticket\Controllers\TicketController;
use Application\Api\Ticket\Controllers\TicketSubjectController;
use Application\Api\User\Controllers\UserController;
use Application\Api\Payment\Controllers\PaymentController;
use Application\Api\Product\Controllers\DiscountController;
use Application\Api\Wallet\Controllers\WalletController;
use Application\Api\Wallet\Controllers\WalletTransactionController;
use Application\Api\Wallet\Controllers\WithdrawalTransactionController;
use Illuminate\Support\Facades\Route;

// Category
Route::prefix('categories')->group(function () {
    Route::get('/active/{brand?}', [CategoryController::class, 'activeProductCategories'])->name('active-product-categories');
    Route::get('/all/{brand?}', [CategoryController::class, 'allCategories'])->name('all-categories');
    Route::get('/{category}/children', [CategoryController::class, 'getCategoryChildren'])->name('category-children');
    Route::get('/{category}', [CategoryController::class, 'show'])->name('category.show');
});

// Colors
Route::prefix('colors')->group(function () {
    Route::get('/active/{brand?}', [ColorController::class, 'activeColors'])->name('active-colors');
    Route::get('/{color}', [ColorController::class, 'show'])->name('color.show');
    Route::get('/', [ColorController::class, 'index'])->name('colors.index');
});


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


// payment
Route::get('/payment/callback', [PaymentController::class, 'callback'])->name('user.payment.callback');
Route::get('/payment', [PaymentController::class, 'payment'])->name('user.payment');
Route::get('/payment/result/{id}', [PaymentController::class, 'show'])->name('user.payment.result');

// discounts
Route::get('/discount/active', [DiscountController::class, 'getActiveDiscount'])->name('discount.active');

// Brand
Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{brand}', [BrandController::class, 'show'])->name('brands.show');
Route::post('/banners', [BrandController::class, 'getBanners'])->name('banners.index');

// posts
Route::get('/posts', [PostController::class, 'getPosts'])->name('site.posts.index');
Route::get('/posts/popular', [PostController::class, 'getPopularPosts'])->name('site.posts.popular');
Route::get('/posts/latest', [PostController::class, 'getLatestPosts'])->name('site.posts.latest');
Route::get('/post/{post}', [PostController::class, 'getPostInfo'])->name('site.post.info');

Route::middleware(['auth:sanctum', 'auth', 'throttle:200,1'])->prefix('profile')->name('profile.')->group(function() {

    // product
    Route::post('products/{product}/favorite', [ProductController::class, 'favorite'])->name('products.favorite');
    Route::get('products/favorite', [ProductController::class, 'getFavoriteProducts'])->name('products.favorite.index');

    // review
    Route::get('my-reviews', [ReviewController::class, 'myReviews'])->name('reviews.index');
    Route::post('reviews/{product}', [ReviewController::class, 'store'])->name('reviews.store');
    Route::patch('reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::get('reviews/{review}/change-status', [ReviewController::class, 'changeStatus'])->name('reviews.change-status');
    Route::get('reviews/{review}/like', [ReviewController::class, 'likeReview'])->name('reviews.like');

    // order
    Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
    Route::post('orders/check-status', [OrderController::class, 'checkOrderStatus'])->name('orders.check-order-status');
    Route::post('orders/{order}/check-discount', [OrderController::class, 'checkDiscount'])->name('orders.check-discount');
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::post('orders/{order}/pay', [OrderController::class, 'payOrder'])->name('orders.pay');

    //


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

    // payment
    Route::post('/payment/manual-payment', [PaymentController::class, 'manualPayment'])->name('user.payment.manual-payment');
    Route::get('/payment/transactions', [PaymentController::class, 'index'])->name('user.payment.transactions');

    // wallet
    Route::get('/wallets', [WalletController::class, 'index']);
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::post('/wallet/top-up', [WalletController::class, 'topUp']);
    Route::post('/wallet/transfer', [WalletController::class, 'transfer']);
    Route::get('/wallet-transaction/{wallet}', [WalletTransactionController::class, 'index']);

    // withdraw
    Route::post('/withdraws', [WithdrawalTransactionController::class, 'store']);
    Route::get('/withdraws', [WithdrawalTransactionController::class, 'index']);

});

// upload files
Route::middleware(['auth:sanctum', 'auth', 'throttle:10,1'])->group(function() {
    Route::post('/upload-image', [FileController::class, 'uploadImage'])->name('site.upload-image');
    Route::post('/upload-video', [FileController::class, 'uploadVideo'])->name('site.upload-video');
    Route::post('/upload-file', [FileController::class, 'uploadFile'])->name('site.upload-file');
});
