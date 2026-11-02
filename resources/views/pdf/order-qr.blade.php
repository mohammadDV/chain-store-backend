<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>کد QR سفارش - {{ $order->code }}</title>
    <style>
        body {
            font-family: vazirmatn, dejavusans, sans-serif;
            direction: rtl;
            color: #1a202c;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        .page {
            width: 100%;
            text-align: center;
            padding-top: 80px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            color: #1a365d;
            margin-bottom: 8px;
        }

        .subtitle {
            font-size: 12px;
            color: #718096;
            margin-bottom: 28px;
        }

        .qr-image {
            width: 280px;
            height: 280px;
        }

        .code {
            margin-top: 20px;
            font-size: 16px;
            font-weight: bold;
            color: #1a365d;
            letter-spacing: 1px;
        }

        .hint {
            margin-top: 8px;
            font-size: 11px;
            color: #718096;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="title">کد QR سفارش</div>
        <div class="subtitle">اسکن برای مشاهده سفارش</div>

        @if(!empty($qrImagePath))
            <img src="{{ $qrImagePath }}" class="qr-image" alt="QR">
        @endif

        <div class="code">{{ $order->code }}</div>
        <div class="hint">{{ __('site.scan_order_qr_hint') }}</div>
    </div>
</body>
</html>
