<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاکتور - {{ $order->code }}</title>
    <style>
        /* Remove @font-face as mpdf may not support it well - use built-in fonts instead */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'dejavusans', 'Tahoma', 'Arial', sans-serif;
            direction: rtl;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }

        .invoice-container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 20px;
        }

        .invoice-title {
            font-size: 32px;
            font-weight: 700;
            color: #000;
        }

        .invoice-info {
            text-align: right;
            font-size: 11px;
        }

        .invoice-info-row {
            margin-bottom: 5px;
        }

        .store-header {
            background: #0066cc;
            color: #fff;
            padding: 10px 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .store-logo {
            text-align: left;
        }

        .logo-text {
            font-size: 24px;
            font-weight: 700;
            color: #0066cc;
        }

        .customer-info {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .customer-info-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 11px;
        }

        .customer-info-row:last-child {
            margin-bottom: 0;
        }

        .customer-info-icon {
            width: 8px;
            height: 8px;
            margin-left: 10px;
            margin-right: 5px;
            background-color: #0066cc;
            border-radius: 50%;
            display: inline-block;
            vertical-align: middle;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .products-table th {
            background: #0066cc;
            color: #fff;
            padding: 10px;
            text-align: center;
            font-weight: 600;
            font-size: 11px;
        }

        .products-table td {
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            font-size: 10px;
        }

        .products-table tr:nth-child(even) {
            background: #f9f9f9;
        }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }

        .product-title {
            text-align: right;
            max-width: 250px;
        }

        .summary-section {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 11px;
        }

        .summary-row:last-child {
            margin-bottom: 0;
        }

        .final-amount {
            background: #0066cc;
            color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: 700;
            text-align: center;
        }

        .amount-in-words {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-bottom: 20px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .footer {
            width: 100%;
            background: #0066cc;
            color: #fff;
            padding: 15px;
            text-align: center;
            font-size: 11px;
            margin-top: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .footer-row {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .footer-row span:first-child {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #fff;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .footer-row:last-child {
            margin-bottom: 0;
        }

        .text-bold {
            font-weight: 700;
        }

        .text-number {
            font-family: 'dejavusans', 'Tahoma', monospace;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div>
                <div class="invoice-title">فاکتور | فروشگاه بوف استور</div>
                <div class="invoice-info" style="margin-top: 15px;">
                    <div class="invoice-info-row">
                        <span class="text-bold">تاریخ چاپ:</span>
                        <span class="text-number">{{ \Morilog\Jalali\Jalalian::fromDateTime($order->created_at)->format('Y/m/d H:i') }}</span>
                    </div>
                    <div class="invoice-info-row" style="margin-top: 5px;">
                        <span class="text-bold">شناسه سفارش:</span>
                        <span class="text-number">{{ $order->code }}</span>
                    </div>
                </div>
            </div>
            <div class="store-logo">
                <div class="logo-text">BOOFSTORE</div>
            </div>
        </div>

        <!-- Store Header Bar -->
        <div class="store-header">
            <span>مشخصات سفارش</span>
        </div>

        <!-- Customer Information -->
        <div class="customer-info">
            <div class="customer-info-row">
                <span class="customer-info-icon"></span>
                <span class="text-bold">نام کامل:</span>
                <span>
                    @if($order->fullname)
                        {{ $order->fullname }}
                    @elseif($order->user && ($order->user->first_name || $order->user->last_name))
                        {{ trim($order->user->first_name . ' ' . $order->user->last_name) }}
                    @else
                        -
                    @endif
                </span>
            </div>
            <div class="customer-info-row">
                <span class="customer-info-icon"></span>
                <span class="text-bold">گیرنده:</span>
                <span>{{ $order->address ?? '-' }}</span>
            </div>
            <div class="customer-info-row">
                <span class="customer-info-icon"></span>
                <span class="text-bold">کدپستی:</span>
                <span class="text-number">{{ $order->postal_code ?? '-' }}</span>
            </div>
            <div class="customer-info-row">
                <span class="customer-info-icon"></span>
                <span class="text-bold">تلفن:</span>
                <span class="text-number">{{ $order->user?->mobile ?? '-' }}</span>
            </div>
            <div class="customer-info-row">
                <span class="customer-info-icon"></span>
                <span class="text-bold">تاریخ سفارش:</span>
                <span class="text-number">{{ \Morilog\Jalali\Jalalian::fromDateTime($order->created_at)->format('H:i d-m-Y') }}</span>
            </div>
        </div>

        <!-- Products Table -->
        <table class="products-table">
            <thead>
                <tr>
                    <th>ردیف</th>
                    <th>شناسه</th>
                    <th>تصویر</th>
                    <th>محصول</th>
                    <th>قیمت</th>
                    <th>درصد تخفیف</th>
                    <th>تعداد</th>
                    <th>مبلغ کل</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->products as $index => $product)
                    @php
                        $pivot = $product->pivot;
                        $productPrice = $product->amount ?? 0;

                        $unitPrice = $pivot->amount ?? $productPrice;
                        $quantity = $pivot->count ?? 1;
                        $itemTotal = $unitPrice * $quantity;
                        $itemDiscount = 0;
                        $finalItemTotal = $itemTotal - $itemDiscount;

                        // Get color and size from pre-loaded arrays to avoid N+1 queries
                        $colorName = '';
                        $sizeName = '';
                        if ($pivot->color_id && isset($colors[$pivot->color_id])) {
                            $colorName = $colors[$pivot->color_id];
                        }
                        if ($pivot->size_id && isset($sizes[$pivot->size_id])) {
                            $sizeName = $sizes[$pivot->size_id];
                        }

                        // Product image - handle S3 or local storage
                        $productImage = '';
                        if ($product->image) {
                            if (str_starts_with($product->image, 'http')) {
                                $productImage = $product->image;
                            } elseif (config('filesystems.default') === 's3') {
                                try {
                                    $productImage = \Storage::disk('s3')->url($product->image);
                                } catch (\Exception $e) {
                                    // Fallback if S3 is not configured
                                    $productImage = '';
                                }
                            } else {
                                // For local storage, use full URL path
                                $productImage = url('storage/' . $product->image);
                            }
                        }
                    @endphp
                    <tr>
                        <td class="text-number">{{ $index + 1 }}</td>
                        <td class="text-number">{{ $product->id }}</td>
                        <td>
                            @if($product->image)
                                <img src="{{ $productImage }}" class="product-image" alt="{{ $product->title }}">
                            @else
                                <div style="width: 60px; height: 60px; background: #ddd; border-radius: 5px;"></div>
                            @endif
                        </td>
                        <td class="product-title">
                            {{ $product->title }}
                            @if($colorName)
                                - {{ $colorName }}
                            @endif
                            @if($sizeName)
                                | سایز {{ $sizeName }}
                            @endif
                            @if($product->brand)
                                | برند {{ $product->brand->title }}
                            @endif
                        </td>
                        <td class="text-number">{{ number_format($unitPrice, 0) }} تومان</td>
                        <td class="text-number">
                            @if($product->discount)
                                {{ $product->discount }}%
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-number">{{ $quantity }}</td>
                        <td class="text-number">{{ number_format($finalItemTotal, 0) }} تومان</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary Section -->
        <div class="summary-section">
            <div class="summary-row">
                <span>تعداد کل محصولات خریداری شده:</span>
                <span class="text-number">{{ $order->product_count }}</span>
            </div>
        </div>

        <div class="summary-section">
            <div class="summary-row">
                <span>مبلغ کل:</span>
                <span class="text-number text-bold">{{ number_format($order->total_amount ?? 0, 0) }} تومان</span>
            </div>
            <div class="summary-row">
                <span>مبلغ تخفیف:</span>
                <span class="text-number">{{ number_format($order->discount_amount ?? 0, 0) }} تومان</span>
                @if($order->discount)
                    <span style="font-size: 10px; color: #666;">(کوپنها: {{ $order->discount->code }})</span>
                @endif
            </div>
            <div class="summary-row">
                <span>مبلغ حمل و نقل:</span>
                <span>حمل و نقل رایگان</span>
            </div>
        </div>

        <!-- Final Amount -->
        <div class="final-amount">
            مبلغ نهایی: {{ number_format($order->amount ?? 0, 0) }} تومان
        </div>

        <!-- Amount in Words -->
        <div class="amount-in-words">
            {{ $amountInWords }}
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-row">
                <span></span>
                <span class="text-number">09123456789</span>
            </div>
            <div class="footer-row">
                <span></span>
                <span>info@boofstore.com</span>
            </div>
            <div class="footer-row">
                <span></span>
                <span>boofstore.com</span>
            </div>
        </div>
    </div>
</body>
</html>

