<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Tidcraft</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .header {
            background-color: #0b3d91;
            background: linear-gradient(135deg, #0b3d91 0%, #1e5bbd 100%);
            color: #ffffff;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-left {
            display: flex;
            align-items: center;
        }
        .logo-box {
            background-color: #ffffff;
            color: #0b3d91;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: inline-block;
            text-align: center;
            line-height: 36px;
            font-weight: bold;
            font-size: 20px;
            margin-right: 12px;
            vertical-align: middle;
            overflow: hidden;
        }
        .brand-info {
            display: flex;
            flex-direction: column;
        }
        .brand-name {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
        }
        .brand-subtitle {
            font-size: 11px;
            color: #d0e1ff;
            margin: 2px 0 0 0;
        }
        .header-right {
            text-align: right;
            font-size: 11px;
            color: #d0e1ff;
        }
        .hero {
            text-align: center;
            padding: 30px 20px 10px 20px;
        }
        .hero-img {
            width: 120px;
            margin-bottom: 20px;
        }
        .hero-title {
            color: #0b3d91;
            font-size: 28px;
            margin: 0 0 10px 0;
        }
        .hero-subtitle {
            color: #555555;
            font-size: 14px;
            margin: 0;
        }
        .content-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin: 20px;
            padding: 20px;
        }
        .greeting {
            font-size: 16px;
            font-weight: bold;
            color: #333333;
            margin-top: 0;
        }
        .greeting span {
            color: #0b3d91;
        }
        .content-text {
            color: #555555;
            font-size: 14px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            background-color: #0d6efd;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: bold;
            margin-top: 15px;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
        }
        .features {
            display: table;
            width: 100%;
            padding: 10px 20px;
            table-layout: fixed;
        }
        .feature-item {
            display: table-cell;
            text-align: center;
            width: 25%;
        }
        .feature-icon {
            background-color: #f0f6ff;
            color: #0d6efd;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-block;
            line-height: 40px;
            font-size: 20px;
            margin-bottom: 8px;
        }
        .feature-text {
            font-size: 11px;
            color: #555555;
            font-weight: bold;
        }
        .help-box {
            background-color: #f0f6ff;
            border-radius: 8px;
            margin: 20px;
            padding: 20px;
            display: table;
            width: calc(100% - 40px);
        }
        .help-icon-col {
            display: table-cell;
            width: 50px;
            vertical-align: top;
        }
        .help-icon {
            background-color: #0d6efd;
            color: #ffffff;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-block;
            line-height: 36px;
            text-align: center;
            font-size: 18px;
        }
        .help-content {
            display: table-cell;
            vertical-align: top;
        }
        .help-title {
            color: #0d6efd;
            font-weight: bold;
            font-size: 14px;
            margin: 0 0 5px 0;
        }
        .help-text {
            color: #666666;
            font-size: 12px;
            margin: 0 0 10px 0;
            line-height: 1.5;
        }
        .btn-outline {
            display: inline-block;
            border: 1px solid #0d6efd;
            color: #0d6efd;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
            background-color: #ffffff;
        }
        .signoff {
            text-align: center;
            padding: 10px 20px 30px 20px;
            color: #555555;
            font-size: 14px;
        }
        .signoff strong {
            display: block;
            margin-top: 5px;
            color: #333333;
        }
        .footer {
            border-top: 1px solid #eeeeee;
            padding: 20px;
            display: table;
            width: calc(100% - 40px);
        }
        .footer-left {
            display: table-cell;
            vertical-align: middle;
        }
        .footer-brand {
            font-weight: bold;
            color: #333333;
            font-size: 16px;
            margin: 0;
        }
        .footer-copy {
            color: #999999;
            font-size: 11px;
            margin: 5px 0 0 0;
        }
        .footer-right {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
            color: #999999;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div style="padding: 20px 0; background-color: #f4f6f9;">
        <div class="email-container">
            
            <!-- Header -->
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background: linear-gradient(135deg, #0b3d91 0%, #1e5bbd 100%);">
                <tr>
                    <td style="padding: 20px;">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td width="50" valign="middle">
                                    @php
                                        $companyShortLogo = $settings['company_short_logo'] ?? null;
                                        $companyLogo = $settings['company_logo'] ?? null;
                                        $embedLogoPath = null;
                                        $logoToUse = $companyShortLogo ?: $companyLogo;
                                        
                                        if ($logoToUse) {
                                            $cleanPath = preg_replace('/^\/?storage\//', '', $logoToUse);
                                            $possiblePaths = [
                                                public_path(ltrim($logoToUse, '/')),
                                                storage_path('app/public/' . $cleanPath),
                                            ];
                                            foreach ($possiblePaths as $path) {
                                                if (file_exists($path)) {
                                                    $embedLogoPath = $path;
                                                    break;
                                                }
                                            }
                                        }
                                    @endphp
                                    @if($embedLogoPath && isset($message))
                                        <div class="logo-box" style="background-color: transparent;">
                                            <img src="{{ $message->embed($embedLogoPath) }}" alt="Logo" style="max-width: 100%; max-height: 100%; vertical-align: middle;">
                                        </div>
                                    @elseif($logoToUse)
                                        <div class="logo-box" style="background-color: transparent;">
                                            <img src="{{ asset(ltrim($logoToUse, '/')) }}" alt="Logo" style="max-width: 100%; max-height: 100%; vertical-align: middle;">
                                        </div>
                                    @else
                                        <div class="logo-box">{{ strtoupper(substr($settings['company_name'] ?? 'T', 0, 1)) }}</div>
                                    @endif
                                </td>
                                <td valign="middle">
                                    <p class="brand-name" style="color:#ffffff;">{{ $settings['company_name'] ?? 'Tidcraft' }}</p>
                                    <p class="brand-subtitle">{{ $settings['company_tagline'] ?? 'Manage &bull; Monitor &bull; Grow' }}</p>
                                </td>
                                <td align="right" class="header-right">
                                    Secure Today.<br>A Safer Tomorrow.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- Dynamic Content Injected Here -->
            <div class="content-box" style="padding: 20px;">
                {!! $dynamicContent !!}
            </div>

            <!-- Sign Off -->
            <div class="signoff">
                We're glad to have you with us!
                <strong>The {{ $settings['company_name'] ?? 'Tidcraft' }} Team</strong>
            </div>

            <!-- Footer -->
            <table class="footer" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="footer-left">
                        <p class="footer-brand">{{ $settings['company_name'] ?? 'Tidcraft' }}</p>
                        <p class="footer-copy">&copy; {{ date('Y') }} {{ $settings['company_name'] ?? 'Tidcraft' }}. All rights reserved.</p>
                    </td>
                    <td class="footer-right">
                        Building a Safer Digital Tomorrow
                    </td>
                </tr>
            </table>

        </div>
    </div>
</body>
</html>