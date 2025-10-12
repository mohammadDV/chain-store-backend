<?php

use Application\Api\User\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed']) // No session needed, just signed
    ->name('verification.verify');

Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
    ->middleware(['auth:sanctum', 'throttle:6,1'])
    ->name('verification.send');


Route::post('/register', [AuthController::class, 'register'])
                ->middleware('guest')
                ->name('register');

Route::post('/login', [AuthController::class, 'login'])
                ->middleware('guest')
                ->name('login');

Route::post('/google/verify', [AuthController::class, 'verify'])
                ->middleware('guest')
                ->name('verify');

Route::post('/complete-register', [AuthController::class, 'completeRegister'])
                ->middleware('auth:sanctum')
                ->name('complete-register');

Route::middleware(['auth:sanctum'])->get('/logout', [AuthController::class, 'logout'])
                ->middleware('auth')
                ->name('logout');

Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
                ->middleware('guest')
                ->name('password.email');

Route::post('/verify-reset-token', [AuthController::class, 'verifyResetToken'])
                ->middleware('guest')
                ->name('password.verify');

Route::post('/reset-password', [AuthController::class, 'resetPassword'])
                ->middleware('guest')
                ->name('password.reset');


Route::get('auth/google', [AuthController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);