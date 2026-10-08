<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Account Has Been Suspended</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f7f6; font-family: 'Inter', sans-serif; color: #333333; }
        table { border-spacing: 0; }
        .container { max-width: 650px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); overflow: hidden; }
        .header { background: linear-gradient(135deg, #0e2a53 0%, #1e4a8b 100%); padding: 30px 40px; color: #ffffff; }
        .header-table { width: 100%; }
        .brand-logo { font-size: 24px; font-weight: 700; }
        .brand-logo-img { padding-right: 10px; }
        .brand-tagline { font-size: 10px; opacity: 0.7; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }
        .header-nav { font-size: 13px; color: #e2e8f0; text-align: center; }
        .header-nav span { margin: 0 5px; opacity: 0.5; }
        .header-motto { font-size: 13px; text-align: right; line-height: 1.4; }
        .header-motto strong { display: block; font-size: 14px; }
        .content { padding: 40px; }
        .title { font-size: 28px; font-weight: 700; color: #1a1a1a; margin: 0 0 15px 0; line-height: 1.3; text-align: center; }
        .title span { color: #ef4444; }
        .intro-text { color: #64748b; font-size: 15px; line-height: 1.6; margin: 0 0 20px 0; }
        .alert-box { background-color: #fef2f2; border-radius: 12px; padding: 25px; margin-bottom: 25px; display: flex; align-items: center; }
        .alert-icon { font-size: 36px; margin-right: 20px; color: #ef4444; }
        .alert-table { width: 100%; font-size: 14px; color: #1e293b; }
        .alert-table td { padding: 4px 0; }
        .alert-label { font-weight: 600; width: 130px; }
        .steps-card { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        .steps-title { font-weight: 700; font-size: 18px; color: #1e293b; margin-bottom: 20px; }
        .step-item { display: inline-block; width: 31%; vertical-align: top; text-align: center; }
        .step-icon { width: 48px; height: 48px; background-color: #eff6ff; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin: 0 auto 10px auto; }
        .step-title { font-weight: 700; font-size: 14px; color: #1e293b; margin-bottom: 5px; }
        .step-desc { font-size: 12px; color: #64748b; line-height: 1.5; padding: 0 5px; }
        .btn-primary { background-color: #0b57d0; color: #ffffff !important; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 16px; display: inline-block; }
        .info-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 25px; }
        .info-table { width: 100%; }
        .info-icon { background-color: #3b82f6; color: #ffffff; width: 32px; height: 32px; border-radius: 50%; text-align: center; line-height: 32px; font-weight: bold; font-size: 18px; }
        .info-title { font-weight: 700; font-size: 14px; color: #1e293b; margin-bottom: 4px; }
        .info-desc { font-size: 13px; color: #64748b; line-height: 1.5; }
        .signoff { font-size: 14px; color: #64748b; margin-top: 30px; }
        .signoff strong { color: #1e293b; display: block; margin-top: 5px; }
        .footer { background-color: #ffffff; padding: 30px 40px; border-top: 1px solid #e2e8f0; }
        .sub-footer { background-color: #0e2a53; color: #94a3b8; padding: 15px 40px; font-size: 12px; }
    </style>
</head>
<body>
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
        
        $appImagePath = public_path('email_images/suspended.png');
        $hasAppImage = file_exists($appImagePath);
        
        $companyName = $settings['company_name'] ?? 'Tidcraft';
        $clientName = $tenant->client->name ?? $tenant->business_name;
    @endphp

    <table width="100%" bgcolor="#f4f7f6" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding: 20px;">
                <div class="container">
                    
                            <!-- Header -->
        <div class="header" style="background: #002244; background-color: #002244; padding: 25px 35px; color: #ffffff;">
            <table class="header-table" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-spacing: 0; width: 100%; border-collapse: collapse;">
                <tbody><tr>
                    <td width="60%" valign="middle" style="vertical-align: middle;">
                        <table cellpadding="0" cellspacing="0" border="0" style="border-spacing: 0; border-collapse: collapse;">
                            <tbody><tr>
                                <td valign="middle" style="vertical-align: middle;">
                                    <a href="{{ config('app.url', url('/')) }}" style="text-decoration: none; display: inline-block;">
                                        <div style="background-color: #FFFFFF; width: 42px; height: 42px; border-radius: 8px; text-align: center; line-height: 42px; overflow: hidden; display: inline-block; vertical-align: middle;">
                                            <img src="{{ !empty($settings['company_logo'] ?? null) ? url($settings['company_logo']) : asset('storage/settings/lUvNMB4ku94XZPnaGVueDO9rYx3TnakYlcPnoqo6.jpg') }}" alt="Logo" width="42" height="42" style="display: block; width: 42px; height: 42px; max-width: 42px; max-height: 42px; object-fit: contain;">
                                        </div>
                                    </a>
                                </td>
                                <td valign="middle" style="padding-left: 12px; vertical-align: middle;">
                                    <a href="{{ config('app.url', url('/')) }}" style="text-decoration: none; color: #ffffff;">
                                        <div style="font-size: 20px; font-weight: 800; color: #FFFFFF; line-height: 1.2;">{{ $settings['company_name'] ?? 'TidCraft' }}</div>
                                        <div style="font-size: 11px; color: #93c5fd; margin-top: 2px; letter-spacing: 0.3px;">Manage • Monitor • Grow</div>
                                    </a>
                                </td>
                            </tr>
                        </tbody></table>
                    </td>
                    <td width="40%" align="right" valign="middle" class="header-motto" style="font-size: 12px; text-align: right; line-height: 1.4; color: #cbd5e1; vertical-align: middle;">
                        Technology<br>
                        <strong style="display: block; font-size: 13px; font-weight: 600; color: #ffffff;">for a Brighter<br>Tomorrow</strong>
                    </td>
                </tr>
            </tbody></table>
        </div>

        <!-- Content -->
                    <div class="content">
                        
                        @if($hasAppImage && isset($message))
                        <div style="text-align: center; margin-bottom: 20px;">
                            <img src="{{ $message->embed($appImagePath) }}" alt="Account Suspended" style="max-width: 100%; height: auto;">
                        </div>
                        @else
                        <div style="text-align: center; margin-bottom: 20px;">
                            <img src="{{ asset('email_images/suspended.png') }}" alt="Account Suspended" style="max-width: 100%; height: auto;">
                        </div>
                        @endif

                        <h1 class="title">Your Account Has Been <span>Suspended</span></h1>
                        
                        <p class="intro-text">
                            <strong>Hi {{ $clientName }},</strong><br><br>
                            We wanted to inform you that your account has been temporarily suspended.
                            As a result, you currently do not have access to your application and its associated services.
                        </p>
                        
                        <!-- Alert Card -->
                        <div class="alert-box">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="60" valign="middle">
                                        <div style="background-color: #ef4444; color: #ffffff; width: 48px; height: 48px; border-radius: 50%; text-align: center; line-height: 48px; font-weight: bold; font-size: 24px;">!</div>
                                    </td>
                                    <td valign="middle">
                                        <table class="alert-table" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td class="alert-label">Account Status</td>
                                                <td>: <span style="color: #ef4444; font-weight: 700;">Suspended</span></td>
                                            </tr>
                                            <tr>
                                                <td class="alert-label">Suspension Date</td>
                                                <td>: {{ now()->format('d F Y, h:i A (T)') }}</td>
                                            </tr>
                                            <tr>
                                                <td class="alert-label">Reason</td>
                                                <td>: {{ $suspensionReason ?? 'Violation of Terms / Payment Issue / Policy Breach' }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Steps Card -->
                        <div class="steps-card">
                            <div class="steps-title">What You Can Do Next?</div>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <!-- Step 1 -->
                                    <td class="step-item">
                                        <div class="step-icon">
                                            <img src="https://img.icons8.com/fluency-systems-regular/48/2563eb/comments.png" width="24" height="24">
                                        </div>
                                        <div class="step-title">1. Review</div>
                                        <div class="step-desc">Check your email for further details.</div>
                                    </td>
                                    <!-- Step 2 -->
                                    <td class="step-item">
                                        <div class="step-icon">
                                            <img src="https://img.icons8.com/fluency-systems-regular/48/2563eb/document.png" width="24" height="24">
                                        </div>
                                        <div class="step-title">2. Resolve</div>
                                        <div class="step-desc">Take the necessary actions to resolve the issue.</div>
                                    </td>
                                    <!-- Step 3 -->
                                    <td class="step-item">
                                        <div class="step-icon">
                                            <img src="https://img.icons8.com/fluency-systems-regular/48/2563eb/new-post.png" width="24" height="24">
                                        </div>
                                        <div class="step-title">3. Contact Us</div>
                                        <div class="step-desc">If you believe this is a mistake, please reach out to our support team.</div>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA -->
                            <div style="text-align: center; margin-top: 30px;">
                                <a href="{{ config('app.url') }}/support" class="btn-primary">Contact Support &rarr;</a>
                            </div>
                        </div>

                        <!-- Info Card -->
                        <div class="info-box">
                            <table class="info-table" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="48" valign="top">
                                        <div class="info-icon">i</div>
                                    </td>
                                    <td valign="top">
                                        <div class="info-title">Need Help?</div>
                                        <div class="info-desc">Our support team is here to assist you. Feel free to open a support ticket from your client portal or reply to this email.</div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="signoff">
                            Thank you for your understanding.<br>
                            <strong>Team {{ $companyName }} Technologies</strong>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="footer">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="30%">
                                    <div class="brand-logo" style="font-size: 20px; color: #1a1a1a;">{{ $companyName }}</div>
                                    <div class="brand-tagline" style="color:#64748b;">Technologies</div>
                                </td>
                                <td width="40%" align="center" style="color: #475569; font-size: 13px;">
                                    Let's Build a<br>Brighter Tomorrow Together.
                                </td>
                                <td width="30%" align="right">
                                    <img src="https://img.icons8.com/ios-filled/50/94a3b8/linkedin.png" width="24" height="24" style="margin-left: 5px;">
                                    <img src="https://img.icons8.com/ios-filled/50/94a3b8/twitter.png" width="24" height="24" style="margin-left: 5px;">
                                    <img src="https://img.icons8.com/ios-filled/50/94a3b8/youtube-play.png" width="24" height="24" style="margin-left: 5px;">
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="sub-footer">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="color: #94a3b8;">&copy; {{ date('Y') }} {{ $companyName }} Technologies. All rights reserved.</td>
                                <td align="right" style="color: #94a3b8;">{{ parse_url(config('app.url'), PHP_URL_HOST) ?? 'www.tidcraft.com' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
