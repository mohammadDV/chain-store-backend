<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاکتور - {{ $order->code }}</title>
    <style>
        body {
            font-family: vazirmatn, dejavusans, sans-serif;
            direction: rtl;
            font-size: 11px;
            color: #1a202c;
            line-height: 1.7;
            background: #ffffff;
        }

        .page {
            width: 100%;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            border-bottom: 3px solid #1a365d;
            padding-bottom: 12px;
        }

        .brand {
            font-size: 22px;
            font-weight: bold;
            color: #1a365d;
            letter-spacing: 0.5px;
        }

        .brand-sub {
            font-size: 10px;
            color: #718096;
            margin-top: 2px;
        }

        .doc-title {
            font-size: 20px;
            font-weight: bold;
            color: #1a365d;
            text-align: left;
        }

        .meta-box {
            text-align: left;
            font-size: 10px;
            color: #2d3748;
            margin-top: 6px;
        }

        .meta-box .label {
            color: #718096;
        }

        .meta-value {
            font-weight: bold;
            color: #1a365d;
        }

        .section-title {
            background: #1a365d;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            padding: 8px 12px;
            margin: 0 0 0 0;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            background: #f7fafc;
            border: 1px solid #e2e8f0;
        }

        .info-table td {
            padding: 8px 12px;
            vertical-align: top;
            font-size: 10px;
            border-bottom: 1px solid #edf2f7;
        }

        .info-table tr:last-child td {
            border-bottom: none;
        }

        .info-label {
            width: 90px;
            color: #718096;
            font-weight: bold;
            white-space: nowrap;
        }

        .info-value {
            color: #1a202c;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .products-table thead th {
            background: #1a365d;
            color: #ffffff;
            padding: 9px 6px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            border: 1px solid #1a365d;
        }

        .products-table tbody td {
            padding: 10px 6px;
            font-size: 10px;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #e2e8f0;
        }

        .products-table tbody tr:nth-child(even) td {
            background: #f7fafc;
        }

        .product-image {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border: 1px solid #e2e8f0;
        }

        .image-placeholder {
            width: 72px;
            height: 72px;
            background: #edf2f7;
            border: 1px solid #e2e8f0;
            text-align: center;
            line-height: 72px;
            color: #a0aec0;
            font-size: 9px;
        }

        .product-title {
            text-align: right;
            padding-right: 8px !important;
        }

        .product-name {
            font-weight: bold;
            color: #1a202c;
            font-size: 10px;
            margin-bottom: 3px;
        }

        .product-meta {
            color: #718096;
            font-size: 9px;
        }

        .totals-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .totals-box {
            width: 55%;
            background: #f7fafc;
            border: 1px solid #e2e8f0;
        }

        .totals-box td {
            padding: 7px 12px;
            font-size: 10px;
            border-bottom: 1px solid #edf2f7;
        }

        .totals-box tr:last-child td {
            border-bottom: none;
        }

        .totals-label {
            color: #4a5568;
            text-align: right;
        }

        .totals-value {
            text-align: left;
            font-weight: bold;
            color: #1a202c;
            white-space: nowrap;
        }

        .final-amount {
            background: #1a365d;
            color: #ffffff;
            padding: 12px 16px;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .amount-words {
            text-align: center;
            font-size: 10px;
            color: #4a5568;
            padding: 8px 12px;
            background: #edf2f7;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }

        .footer {
            width: 100%;
            border-collapse: collapse;
            background: #1a365d;
            color: #ffffff;
            margin-top: 10px;
        }

        .footer td {
            padding: 10px 12px;
            font-size: 9px;
            text-align: center;
        }

        .muted {
            color: #718096;
        }

        .nowrap {
            white-space: nowrap;
        }
    </style>
</head>
<body>
@php
    $orderJalali = \Morilog\Jalali\Jalalian::fromDateTime($order->created_at);
    $printedJalali = \Morilog\Jalali\Jalalian::now();
    $customerName = $order->fullname
        ?: ($order->user ? trim(($order->user->first_name ?? '') . ' ' . ($order->user->last_name ?? '')) : '')
        ?: '-';
@endphp
<div class="page">
    <table class="header-table">
        <tr>
            <td width="55%" style="vertical-align: top;">
                <div class="brand">BOOFSTORE</div>
                <div class="brand-sub">فروشگاه اینترنتی بوف استور</div>
            </td>
            <td width="45%" style="vertical-align: top;">
                <div class="doc-title">فاکتور فروش</div>
                <div class="meta-box">
                    <div>
                        <span class="label">شناسه سفارش:</span>
                        <span class="meta-value">{{ $order->code }}</span>
                    </div>
                    <div>
                        <span class="label">تاریخ سفارش:</span>
                        <span class="meta-value">{{ $orderJalali->format('Y/m/d') }} — {{ $orderJalali->format('H:i') }}</span>
                    </div>
                    <div>
                        <span class="label">تاریخ چاپ:</span>
                        <span>{{ $printedJalali->format('Y/m/d H:i') }}</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">مشخصات گیرنده</div>
    <table class="info-table">
        <tr>
            <td class="info-label">نام کامل</td>
            <td class="info-value" width="40%">{{ $customerName }}</td>
            <td class="info-label">تلفن</td>
            <td class="info-value">{{ $order->user?->mobile ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">کدپستی</td>
            <td class="info-value">{{ $order->postal_code ?? '-' }}</td>
            <td class="info-label">تعداد اقلام</td>
            <td class="info-value">{{ $order->product_count ?? $order->products->count() }}</td>
        </tr>
        <tr>
            <td class="info-label">آدرس</td>
            <td class="info-value" colspan="3">{{ $order->address ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">اقلام سفارش</div>
    <table class="products-table">
        <thead>
            <tr>
                <th width="6%">ردیف</th>
                <th width="14%">تصویر</th>
                <th width="34%">محصول</th>
                <th width="14%">قیمت واحد</th>
                <th width="8%">تخفیف</th>
                <th width="8%">تعداد</th>
                <th width="16%">مبلغ کل</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->products as $index => $product)
                @php
                    $pivot = $product->pivot;
                    $unitPrice = $pivot->amount ?? ($product->amount ?? 0);
                    $quantity = $pivot->count ?? 1;
                    $finalItemTotal = $unitPrice * $quantity;

                    $colorName = ($pivot->color_id && isset($colors[$pivot->color_id]))
                        ? $colors[$pivot->color_id]
                        : '';
                    $sizeName = ($pivot->size_id && isset($sizes[$pivot->size_id]))
                        ? $sizes[$pivot->size_id]
                        : '';

                    $productImage = $productImages[$product->id] ?? null;

                    $metaParts = array_filter([
                        $colorName ? 'رنگ: ' . $colorName : null,
                        $sizeName ? 'سایز: ' . $sizeName : null,
                        $product->brand ? 'برند: ' . $product->brand->title : null,
                        $product->code ? 'کد: ' . $product->code : null,
                    ]);
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        @if($productImage)
                            <img src="{{ $productImage }}" class="product-image" alt="{{ $product->title }}">
                        @else
                            <div class="image-placeholder">بدون تصویر</div>
                        @endif
                    </td>
                    <td class="product-title">
                        <div class="product-name">{{ $product->title }}</div>
                        @if(count($metaParts))
                            <div class="product-meta">{{ implode(' | ', $metaParts) }}</div>
                        @endif
                    </td>
                    <td class="nowrap">{{ number_format($unitPrice, 0) }}</td>
                    <td>
                        @if($product->discount)
                            {{ $product->discount }}٪
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $quantity }}</td>
                    <td class="nowrap">{{ number_format($finalItemTotal, 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-wrap">
        <tr>
            <td></td>
            <td class="totals-box">
                <table width="100%" style="border-collapse: collapse;">
                    <tr>
                        <td class="totals-label">جمع مبلغ کالاها</td>
                        <td class="totals-value">{{ number_format($order->total_amount ?? 0, 0) }} تومان</td>
                    </tr>
                    <tr>
                        <td class="totals-label">
                            مبلغ تخفیف
                            @if($order->discount)
                                <span class="muted">({{ $order->discount->code }})</span>
                            @endif
                        </td>
                        <td class="totals-value">{{ number_format($order->discount_amount ?? 0, 0) }} تومان</td>
                    </tr>
                    <tr>
                        <td class="totals-label">هزینه ارسال</td>
                        <td class="totals-value">رایگان</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="final-amount">
        مبلغ قابل پرداخت: {{ number_format($order->amount ?? 0, 0) }} تومان
    </div>

    <div class="amount-words">
        {{ $amountInWords }}
    </div>

    <table class="footer">
        <tr>
            <td>۰۹۱۲۳۴۵۶۷۸۹</td>
            <td>info@boofstore.com</td>
            <td>boofstore.com</td>
        </tr>
    </table>
</div>
</body>
</html>
