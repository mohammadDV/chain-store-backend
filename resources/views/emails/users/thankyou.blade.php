@extends('emails.layouts.base-peyda')

@section('title', __('site.Welcome to') . ' ' . config('app.name'))

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
    <h2 class="title">{{ __('site.Welcome to') }} {{ config('app.name') }}, {{ $user->first_name }}!</h2>

    <!-- Content Section -->
    <p class="content">
        {{ __('site.Thank you for registering with us') }}. {{ __('site.We are excited to have you on board') }}.
    </p>

    <!-- Welcome Message -->
    <div class="welcome-message">
        🎉 {{ __('site.Welcome message') }} {{ config('app.name') }} داریم.
    </div>

    <!-- CTA Button -->
    <a href="{{ config('app.url') }}" class="button">
        {{ __('site.Go to Dashboard') }}
    </a>

    <!-- Footer -->
    <div class="footer">
        <p>{{ __('site.If you have any questions, feel free to reply to this email') }}.</p>
        <p>{{ __('site.Thanks') }},<br>{{ __('site.The') }} {{ config('app.name') }} {{ __('site.Team') }}</p>
    </div>
@endsection
