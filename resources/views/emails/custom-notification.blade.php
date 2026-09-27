@extends('emails.layouts.base-peyda')

@section('title', $title)

@section('content')
    <h1 class="title" style="font-family:'Estedad',Tahoma,Arial,sans-serif;font-size:22px;font-weight:700;color:#333333;line-height:1.5;margin:0 0 16px;">
        {{ $title }}
    </h1>

    <p class="text" style="font-family:'Estedad',Tahoma,Arial,sans-serif;font-size:15px;color:#555555;line-height:1.9;margin:0 0 24px;white-space:pre-line;">
        {{ $content }}
    </p>

    @if(!empty($actionUrl))
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 8px;">
            <tr>
                <td align="center" bgcolor="#FF385C" style="border-radius:8px;">
                    <a href="{{ $actionUrl }}" class="button" style="display:inline-block;background-color:#FF385C;color:#ffffff;font-family:'Estedad',Tahoma,Arial,sans-serif;font-size:15px;font-weight:600;padding:14px 28px;border-radius:8px;text-decoration:none;">
                        رفتن به سایت
                    </a>
                </td>
            </tr>
        </table>
    @endif
@endsection

@section('footer')
    این ایمیل از طرف {{ config('app.name') }} ارسال شده است.
@endsection
