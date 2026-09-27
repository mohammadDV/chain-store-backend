<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', config('app.name'))</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td, a, p, h1, h2, h3 { font-family: Tahoma, Arial, sans-serif !important; }
    </style>
    <![endif]-->
    <style>
        @font-face {
            font-family: 'Estedad';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: url('{{ asset('fonts/estedad/Estedad-FD-Regular.woff2') }}') format('woff2');
        }
        @font-face {
            font-family: 'Estedad';
            font-style: normal;
            font-weight: 500;
            font-display: swap;
            src: url('{{ asset('fonts/estedad/Estedad-FD-Medium.woff2') }}') format('woff2');
        }
        @font-face {
            font-family: 'Estedad';
            font-style: normal;
            font-weight: 600;
            font-display: swap;
            src: url('{{ asset('fonts/estedad/Estedad-FD-SemiBold.woff2') }}') format('woff2');
        }
        @font-face {
            font-family: 'Estedad';
            font-style: normal;
            font-weight: 700;
            font-display: swap;
            src: url('{{ asset('fonts/estedad/Estedad-FD-Bold.woff2') }}') format('woff2');
        }

        body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            background-color: #f3f2f0;
            direction: rtl;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            border: 0;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
        }

        a {
            text-decoration: none;
        }

        .wrapper {
            width: 100%;
            background-color: #f3f2f0;
        }

        .container {
            width: 100%;
            max-width: 600px;
        }

        .card {
            background-color: #ffffff;
            border: 1px solid #e8e6e3;
        }

        .brand {
            font-family: 'Estedad', Tahoma, Arial, sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: #333333;
            letter-spacing: 0.5px;
        }

        .brand-accent {
            color: #FF385C;
        }

        .title {
            font-family: 'Estedad', Tahoma, Arial, sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: #333333;
            line-height: 1.5;
            margin: 0;
        }

        .text {
            font-family: 'Estedad', Tahoma, Arial, sans-serif;
            font-size: 15px;
            font-weight: 400;
            color: #555555;
            line-height: 1.9;
            margin: 0;
        }

        .button {
            display: inline-block;
            background-color: #FF385C;
            color: #ffffff !important;
            font-family: 'Estedad', Tahoma, Arial, sans-serif;
            font-size: 15px;
            font-weight: 600;
            padding: 14px 28px;
            border-radius: 8px;
            text-decoration: none;
        }

        .note {
            font-family: 'Estedad', Tahoma, Arial, sans-serif;
            font-size: 13px;
            font-weight: 400;
            color: #666666;
            line-height: 1.8;
            background-color: #f7f6f4;
            border: 1px solid #e8e6e3;
            border-radius: 8px;
            padding: 14px 16px;
        }

        .url-box {
            font-family: Tahoma, Arial, sans-serif;
            font-size: 12px;
            color: #666666;
            line-height: 1.7;
            background-color: #fafafa;
            border: 1px solid #e8e6e3;
            border-radius: 8px;
            padding: 14px 16px;
            word-break: break-all;
            direction: ltr;
            text-align: left;
        }

        .footer-text {
            font-family: 'Estedad', Tahoma, Arial, sans-serif;
            font-size: 12px;
            color: #999999;
            line-height: 1.8;
        }

        @media only screen and (max-width: 620px) {
            .container {
                width: 100% !important;
            }

            .px {
                padding-left: 20px !important;
                padding-right: 20px !important;
            }

            .title {
                font-size: 20px !important;
            }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#f3f2f0;direction:rtl;">
    <table role="presentation" class="wrapper" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f3f2f0;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">
                    <tr>
                        <td align="center" style="padding-bottom: 20px;">
                            <div class="brand" style="font-family:'Estedad',Tahoma,Arial,sans-serif;font-size:22px;font-weight:700;color:#333333;">
                                BOOF<span class="brand-accent" style="color:#FF385C;">STORE</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="card" style="background-color:#ffffff;border:1px solid #e8e6e3;border-radius:12px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td class="px" style="padding: 36px 40px;">
                                        @yield('content')
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 24px 8px 8px;">
                            <p class="footer-text" style="margin:0;font-family:'Estedad',Tahoma,Arial,sans-serif;font-size:12px;color:#999999;line-height:1.8;">
                                @yield('footer', 'این ایمیل از طرف '.config('app.name').' ارسال شده است.')
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
