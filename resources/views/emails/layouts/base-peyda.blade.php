<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <style>
        /* Peyda Font Definitions for Email */
        @font-face {
            font-family: PeydaWebFaNum;
            font-style: normal;
            font-weight: 100;
            src: url('{{ asset('fonts/peyda/fonts/woff2/PeydaWebFaNum-Thin.woff2') }}') format('woff2'),
                 url('{{ asset('fonts/peyda/fonts/woff/PeydaWebFaNum-Thin.woff') }}') format('woff');
        }
        @font-face {
            font-family: PeydaWebFaNum;
            font-style: normal;
            font-weight: 200;
            src: url('{{ asset('fonts/peyda/fonts/woff2/PeydaWebFaNum-ExtraLight.woff2') }}') format('woff2'),
                 url('{{ asset('fonts/peyda/fonts/woff/PeydaWebFaNum-ExtraLight.woff') }}') format('woff');
        }
        @font-face {
            font-family: PeydaWebFaNum;
            font-style: normal;
            font-weight: 300;
            src: url('{{ asset('fonts/peyda/fonts/woff2/PeydaWebFaNum-Light.woff2') }}') format('woff2'),
                 url('{{ asset('fonts/peyda/fonts/woff/PeydaWebFaNum-Light.woff') }}') format('woff');
        }
        @font-face {
            font-family: PeydaWebFaNum;
            font-style: normal;
            font-weight: 400;
            src: url('{{ asset('fonts/peyda/fonts/woff2/PeydaWebFaNum-Regular.woff2') }}') format('woff2'),
                 url('{{ asset('fonts/peyda/fonts/woff/PeydaWebFaNum-Regular.woff') }}') format('woff');
        }
        @font-face {
            font-family: PeydaWebFaNum;
            font-style: normal;
            font-weight: 500;
            src: url('{{ asset('fonts/peyda/fonts/woff2/PeydaWebFaNum-Medium.woff2') }}') format('woff2'),
                 url('{{ asset('fonts/peyda/fonts/woff/PeydaWebFaNum-Medium.woff') }}') format('woff');
        }
        @font-face {
            font-family: PeydaWebFaNum;
            font-style: normal;
            font-weight: 600;
            src: url('{{ asset('fonts/peyda/fonts/woff2/PeydaWebFaNum-SemiBold.woff2') }}') format('woff2'),
                 url('{{ asset('fonts/peyda/fonts/woff/PeydaWebFaNum-SemiBold.woff') }}') format('woff');
        }
        @font-face {
            font-family: PeydaWebFaNum;
            font-style: normal;
            font-weight: 700;
            src: url('{{ asset('fonts/peyda/fonts/woff2/PeydaWebFaNum-Bold.woff2') }}') format('woff2'),
                 url('{{ asset('fonts/peyda/fonts/woff/PeydaWebFaNum-Bold.woff') }}') format('woff');
        }
        @font-face {
            font-family: PeydaWebFaNum;
            font-style: normal;
            font-weight: 800;
            src: url('{{ asset('fonts/peyda/fonts/woff2/PeydaWebFaNum-ExtraBold.woff2') }}') format('woff2'),
                 url('{{ asset('fonts/peyda/fonts/woff/PeydaWebFaNum-ExtraBold.woff') }}') format('woff');
        }
        @font-face {
            font-family: PeydaWebFaNum;
            font-style: normal;
            font-weight: 900;
            src: url('{{ asset('fonts/peyda/fonts/woff2/PeydaWebFaNum-Black.woff2') }}') format('woff2'),
                 url('{{ asset('fonts/peyda/fonts/woff/PeydaWebFaNum-Black.woff') }}') format('woff');
        }

        /* Base Email Styles with Peyda Font */
        body {
            margin: 0;
            padding: 0;
            font-family: 'PeydaWebFaNum', 'Tahoma', 'Arial', sans-serif !important;
            background-color: #f5f5f5;
            direction: rtl;
            line-height: 1.6;
        }

        * {
            font-family: 'PeydaWebFaNum', 'Tahoma', 'Arial', sans-serif !important;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .email-card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
            margin: 20px 0;
        }

        .logo {
            margin-bottom: 30px;
        }

        .logo-text {
            font-size: 28px;
            font-weight: bold;
            color: rgba(230, 78, 181, 1);
            margin: 0;
            position: relative;
            display: inline-block;
        }

        .logo-dots {
            position: absolute;
            right: -25px;
            top: 50%;
            transform: translateY(-50%);
        }

        .dot {
            width: 6px;
            height: 6px;
            background-color: rgba(230, 78, 181, 1);
            border-radius: 50%;
            margin: 2px 0;
            display: block;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            color: #000000;
            margin: 20px 0;
            line-height: 1.4;
        }

        .content {
            font-size: 16px;
            color: #333333;
            line-height: 1.6;
            margin: 20px 0 30px 0;
            text-align: center;
        }

        .button {
            display: inline-block;
            background-color: rgba(230, 78, 181, 1);
            color: #ffffff;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0;
            transition: background-color 0.3s ease;
        }

        .button:hover {
            background-color: rgba(200, 68, 161, 1);
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
            font-size: 14px;
            color: #666666;
        }

        .welcome-message {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #155724;
            font-size: 14px;
            text-align: center;
        }

        .warning-text {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
            font-size: 14px;
            text-align: center;
        }

        .url-text {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #495057;
            font-size: 12px;
            word-break: break-all;
            direction: ltr;
            text-align: center;
        }

        .button {
            color: #fff !important;
        }

        /* Responsive Design */
        @media only screen and (max-width: 600px) {
            .email-container {
                padding: 10px;
            }

            .email-card {
                padding: 20px;
            }

            .title {
                font-size: 20px;
            }

            .content {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-card">
            @yield('content')
        </div>
    </div>
</body>
</html>
