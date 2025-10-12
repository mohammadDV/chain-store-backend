@extends('emails.layouts.base-peyda')

@section('title', $title)

@section('content')
    <!-- Logo Section -->
    <div class="logo">
        <h1 class="logo-text">
            <span class="logo-dots">
                <span class="dot"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </span>
            Oshtow
        </h1>
    </div>

    <!-- Title Section -->
    <h2 class="title">{{ $title }}</h2>

    <!-- Content Section -->
    <p class="content">{{ $content }}</p>

    <!-- CTA Button -->
    <a href="{{ $actionUrl ?? '#' }}" class="button">
        رفتن به سایت
    </a>

    <!-- Footer -->
    <div class="footer">
        <p>این ایمیل از طرف {{ config('app.name') }} ارسال شده است</p>
        <p>اگر سوالی دارید، لطفاً با ما تماس بگیرید</p>
    </div>
@endsection
