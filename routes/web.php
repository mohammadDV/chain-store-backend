<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Domain\User\Models\User;

Route::get('/', function () {
    return view('welcome');
});

// Test route to preview email templates with Peyda font
Route::get('/test-email/{template}', function ($template) {
    $user = User::first();

    if (!$user) {
        return 'No user found for testing';
    }

    switch ($template) {
        case 'thankyou':
            return view('emails.users.thankyou', ['user' => $user]);
        case 'password-reset':
            return view('emails.users.password-reset', [
                'user' => $user,
                'resetUrl' => 'https://example.com/reset-password?token=test-token'
            ]);
        case 'custom-notification':
            return view('emails.custom-notification', [
                'title' => 'Test Notification',
                'content' => 'This is a test notification with Peyda font.',
                'actionUrl' => 'https://example.com'
            ]);
        case 'email-verification':
            return view('emails.users.email-verification', [
                'user' => $user,
                'verificationUrl' => 'https://example.com/verify-email?id=123&hash=abc123'
            ]);
        default:
            return 'Invalid template. Available: thankyou, password-reset, custom-notification, email-verification';
    }
})->name('test.email');

// Proxy route for S3 images to avoid CORS issues
Route::get('storage/s3/{path}', function ($path) {
    $s3Path = urldecode($path);
    $disk = Storage::disk('s3');

    if (!$disk->exists($s3Path)) {
        abort(404);
    }

    $file = $disk->get($s3Path);
    $extension = pathinfo($s3Path, PATHINFO_EXTENSION);
    $mimeType = match($extension) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        default => 'application/octet-stream',
    };

    return response($file, 200, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.*');
