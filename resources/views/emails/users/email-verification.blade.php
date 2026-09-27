@extends('emails.layouts.base-peyda')

@section('title', __('site.Verify Email Address'))

@section('content')
    <h1 class="title" style="font-family:'Estedad',Tahoma,Arial,sans-serif;font-size:22px;font-weight:700;color:#333333;line-height:1.5;margin:0 0 16px;">
        {{ __('site.Verify Email Address') }}
    </h1>

    <p class="text" style="font-family:'Estedad',Tahoma,Arial,sans-serif;font-size:15px;color:#555555;line-height:1.9;margin:0 0 24px;">
        {{ __('site.Hello') }}@if(!empty($user->first_name)) {{ $user->first_name }}@endif،
        <br><br>
        {{ __('site.Please click the button below to verify your email address.') }}
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 24px;">
        <tr>
            <td align="center" bgcolor="#FF385C" style="border-radius:8px;">
                <a href="{{ $verificationUrl }}" class="button" style="display:inline-block;background-color:#FF385C;color:#ffffff;font-family:'Estedad',Tahoma,Arial,sans-serif;font-size:15px;font-weight:600;padding:14px 28px;border-radius:8px;text-decoration:none;">
                    {{ __('site.Verify Email Address') }}
                </a>
            </td>
        </tr>
    </table>

    <div class="note" style="font-family:'Estedad',Tahoma,Arial,sans-serif;font-size:13px;color:#666666;line-height:1.8;background-color:#f7f6f4;border:1px solid #e8e6e3;border-radius:8px;padding:14px 16px;margin:0 0 20px;">
        {{ __('site.This verification link will expire in 60 minutes.') }}
        <br>
        {{ __('site.If you did not create an account, no further action is required.') }}
    </div>

    <div class="url-box" style="font-family:Tahoma,Arial,sans-serif;font-size:12px;color:#666666;line-height:1.7;background-color:#fafafa;border:1px solid #e8e6e3;border-radius:8px;padding:14px 16px;word-break:break-all;direction:ltr;text-align:left;">
        <span style="font-family:'Estedad',Tahoma,Arial,sans-serif;display:block;direction:rtl;text-align:right;margin-bottom:8px;color:#777777;">
            {{ __('site.If you are having trouble clicking the "Verify Email Address" button, copy and paste the URL below into your web browser:') }}
        </span>
        {{ $verificationUrl }}
    </div>
@endsection

@section('footer')
    {{ __('site.Thanks') }} — {{ config('app.name') }}
@endsection
