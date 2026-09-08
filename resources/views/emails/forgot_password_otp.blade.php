<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f7f6;
            color: #333333;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .header {
            background: linear-gradient(135deg, #002244, #0055ff);
            color: white;
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-content {
            display: table;
            width: 100%;
        }
        .header-left {
            display: table-cell;
            vertical-align: middle;
        }
        .header-right {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
            font-size: 12px;
            opacity: 0.9;
        }
        .brand {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            display: flex;
            align-items: center;
        }
        .brand-logo-img {
            width: 36px; 
            height: 36px; 
            background: white; 
            padding: 4px; 
            border-radius: 6px; 
            margin-right: 12px;
            vertical-align: middle;
        }
        .brand-sub {
            font-size: 11px;
            opacity: 0.8;
            margin-top: 4px;
        }
        .content {
            padding: 40px 30px;
            text-align: center;
        }
        .icon-container {
            width: 80px;
            height: 80px;
            background: #e6f0ff;
            border-radius: 50%;
            margin: 0 auto 20px;
            text-align: center;
            line-height: 80px;
        }
        .icon-container img {
            width: 40px;
            height: 40px;
            vertical-align: middle;
            display: inline-block;
        }
        .icon-lock-overlay {
            position: absolute;
            bottom: -5px;
            right: 5px;
            background: white;
            border-radius: 50%;
            padding: 4px;
            width: 24px;
            height: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #002244;
            margin: 0 0 15px;
            font-size: 26px;
        }
        p {
            color: #666666;
            line-height: 1.6;
            margin: 0 0 30px;
            font-size: 15px;
        }
        .otp-container {
            background: #f0f7ff;
            border-radius: 12px;
            padding: 30px 20px;
            margin-bottom: 30px;
        }
        .otp-title {
            color: #002244;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 16px;
        }
        .otp-digits {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .digit {
            background: white;
            color: #0055ff;
            font-size: 32px;
            font-weight: bold;
            width: 45px;
            height: 60px;
            line-height: 60px;
            border-radius: 8px;
            display: inline-block;
            margin: 0 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            text-align: center;
        }
        .validity {
            color: #555;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .steps {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            table-layout: fixed;
        }
        .step {
            display: table-cell;
            text-align: center;
            padding: 0 10px;
            vertical-align: top;
        }
        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin: 0 auto 10px;
            text-align: center;
            line-height: 40px;
        }
        .step-icon img {
            width: 20px;
            height: 20px;
            vertical-align: middle;
            display: inline-block;
        }
        .step-1 { background: #e6f0ff; }
        .step-2 { background: #e6ffe6; }
        .step-3 { background: #e6f0ff; }
        .step-4 { background: #ffe6e6; }
        
        .step-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 5px;
            color: #333;
        }
        .step-desc {
            font-size: 11px;
            color: #777;
            line-height: 1.4;
        }
        .warning-box {
            background: #fff0f0;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            align-items: flex-start;
            text-align: left;
            margin-bottom: 30px;
        }
        .warning-icon {
            background: #cc0000;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-block;
            text-align: center;
            line-height: 24px;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .warning-title {
            color: #cc0000;
            font-weight: bold;
            margin: 0 0 5px;
            font-size: 14px;
        }
        .warning-text {
            margin: 0;
            font-size: 13px;
            color: #555;
        }
        .footer {
            border-top: 1px solid #eeeeee;
            padding: 20px 30px;
            display: table;
            width: 100%;
            box-sizing: border-box;
        }
        .footer-left {
            display: table-cell;
            vertical-align: middle;
        }
        .footer-right {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
        }
        .footer-brand {
            font-weight: bold;
            color: #002244;
            font-size: 18px;
            margin: 0 0 5px;
        }
        .footer-text {
            color: #888;
            font-size: 12px;
            margin: 0;
        }
        @media only screen and (max-width: 600px) {
            .steps { display: block; }
            .step { display: block; width: 100%; margin-bottom: 20px; }
            .header-content, .footer { display: block; text-align: center; }
            .header-left, .header-right, .footer-left, .footer-right { display: block; text-align: center; width: 100%; }
            .header-right, .footer-right { margin-top: 15px; }
            .digit { width: 35px; height: 50px; line-height: 50px; font-size: 24px; margin: 0 3px; }
        }
    </style>
</head>
<body>
    @php
        $companyLogo = \App\Models\Setting::where('key', 'company_logo')->value('value');
        $companyName = \App\Models\Setting::where('key', 'company_name')->value('value') ?? 'YourBrand';
        $companyTagline = \App\Models\Setting::where('key', 'company_tagline')->value('value') ?? 'Secure Access. Stronger Together.';
        
        $logoUrl = null;
        $embedLogoPath = null;
 
        if ($companyLogo) {
            if (str_starts_with($companyLogo, 'http')) {
                $logoUrl = $companyLogo;
            } else {
                $logoUrl = asset('/' . ltrim($companyLogo, '/'));
                
                $cleanPath = preg_replace('/^\/?storage\//', '', $companyLogo);
                $possiblePaths = [
                    public_path(ltrim($companyLogo, '/')),
                    storage_path('app/public/' . $cleanPath),
                ];
                
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $embedLogoPath = $path;
                        break;
                    }
                }
            }
        }
    @endphp
    <div class="container">
        <!-- Header -->
        <div class="header">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td align="left" valign="middle">
                        <div class="brand">
                            @if($embedLogoPath && isset($message))
                                <img src="{{ $message->embed($embedLogoPath) }}" alt="Logo" class="brand-logo-img">
                            @elseif($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Logo" class="brand-logo-img">
                            @else
                                <img src="https://img.icons8.com/ios-filled/50/002244/security-checked.png" alt="Logo" class="brand-logo-img">
                            @endif
                            <span style="vertical-align: middle;">{{ $companyName }}</span>
                        </div>
                        <div class="brand-sub">{{ $companyTagline }}</div>
                    </td>
                    <td align="right" valign="middle" style="font-size: 12px; color: #e0e0e0;">
                        <div>Account Security</div>
                        <div style="font-weight: bold; color: white;">Our Priority</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Content -->
        <div class="content">
            <div style="margin-bottom: 20px;">
                <div class="icon-container">
                    <img src="https://img.icons8.com/color/96/000000/secured-letter--v1.png" alt="Mail" style="width: 50px; height: 50px;">
                </div>
            </div>
            
            <h1>Your OTP for Password Reset</h1>
            <p>We received a request to reset the password for your account.<br>Use the OTP below to proceed.</p>
            
            <!-- OTP Box -->
            <div class="otp-container">
                <div class="otp-title">Your One-Time Password (OTP)</div>
                <div class="otp-digits">
                    @foreach(str_split($otp) as $digit)
                        <div class="digit">{{ $digit }}</div>
                    @endforeach
                </div>
                <div class="validity">
                    <img src="https://img.icons8.com/color/48/000000/clock--v1.png" style="width: 16px; height: 16px; vertical-align: middle;" alt="Clock">
                    <span style="vertical-align: middle;">This OTP is valid for <strong style="color: #0055ff;">15 minutes</strong>.</span>
                </div>
            </div>

            <!-- Steps -->
            <table class="steps" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td class="step">
                        <div class="step-icon step-1">
                            <img src="https://img.icons8.com/color/48/000000/lock.png" alt="Enter OTP">
                        </div>
                        <div class="step-title">1. Enter OTP</div>
                        <div class="step-desc">Go back to the password reset page and enter the above OTP.</div>
                    </td>
                    <td class="step">
                        <div class="step-icon step-2">
                            <img src="https://img.icons8.com/color/48/000000/checked--v1.png" alt="Verify">
                        </div>
                        <div class="step-title">2. Verify</div>
                        <div class="step-desc">Once verified, you can set a new password for your account.</div>
                    </td>
                    <td class="step">
                        <div class="step-icon step-3">
                            <img src="https://img.icons8.com/color/48/000000/user.png" alt="Secure">
                        </div>
                        <div class="step-title">3. Keep it Secure</div>
                        <div class="step-desc">Do not share this OTP with anyone.</div>
                    </td>
                    <td class="step">
                        <div class="step-icon step-4">
                            <img src="https://img.icons8.com/color/48/000000/alarm-clock--v1.png" alt="Expires">
                        </div>
                        <div class="step-title">4. Expires Soon</div>
                        <div class="step-desc">This OTP will expire in 15 minutes.</div>
                    </td>
                </tr>
            </table>

            <!-- Warning -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 30px;">
                <tr>
                    <td style="background: #fff0f0; border-radius: 8px; padding: 20px; text-align: left;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td width="40" valign="top">
                                    <div class="warning-icon">!</div>
                                </td>
                                <td>
                                    <h3 class="warning-title">Didn't request this?</h3>
                                    <p class="warning-text">If you did not request a password reset, please ignore this email or contact our support team immediately to keep your account secure.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <table class="footer" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td align="left">
                    <div class="footer-brand">{{ $companyName }}</div>
                    <div class="footer-text">&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</div>
                </td>
                <td align="right">
                    <div style="margin-bottom: 5px;">
                        <img src="https://img.icons8.com/color/48/000000/linkedin.png" alt="in" style="width: 16px; height: 16px; margin: 0 2px;">
                        <img src="https://img.icons8.com/color/48/000000/twitter--v1.png" alt="tw" style="width: 16px; height: 16px; margin: 0 2px;">
                        <img src="https://img.icons8.com/color/48/000000/youtube-play.png" alt="yt" style="width: 16px; height: 16px; margin: 0 2px;">
                        <img src="https://img.icons8.com/color/48/000000/domain--v1.png" alt="web" style="width: 16px; height: 16px; margin: 0 2px;">
                    </div>
                    <div class="footer-text">Secure Access. A Safer Tomorrow.</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
