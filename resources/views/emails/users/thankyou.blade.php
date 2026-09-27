@extends('emails.layouts.base-peyda')

@section('title', __('site.Welcome to') . ' ' . config('app.name'))

@section('content')
    <h1 class="title" style="font-family:'Estedad',Tahoma,Arial,sans-serif;font-size:22px;font-weight:700;color:#333333;line-height:1.5;margin:0 0 16px;">
        {{ __('site.Welcome to') }} {{ config('app.name') }}
        @if(!empty($user->first_name))
            ، {{ $user->first_name }}
        @endif
    </h1>

    <p class="text" style="font-family:'Estedad',Tahoma,Arial,sans-serif;font-size:15px;color:#555555;line-height:1.9;margin:0 0 24px;">
        {{ __('site.Thank you for registering with us') }}.
        {{ __('site.We are excited to have you on board') }}.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 28px;">
        <tr>
            <td align="center" bgcolor="#FF385C" style="border-radius:8px;">
                <a href="{{ config('app.frontend_url', config('app.url')) }}" class="button" style="display:inline-block;background-color:#FF385C;color:#ffffff;font-family:'Estedad',Tahoma,Arial,sans-serif;font-size:15px;font-weight:600;padding:14px 28px;border-radius:8px;text-decoration:none;">
                    {{ __('site.Go to Dashboard') }}
                </a>
            </td>
        </tr>
    </table>

    <p class="text" style="font-family:'Estedad',Tahoma,Arial,sans-serif;font-size:14px;color:#777777;line-height:1.8;margin:0;">
        {{ __('site.If you have any questions, feel free to reply to this email') }}.
    </p>
@endsection

@section('footer')
    {{ __('site.Thanks') }} — {{ config('app.name') }}
@endsection
