<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Application is Ready</title>
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
        .badge { background-color: #eff6ff; color: #2563eb; padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-block; margin-bottom: 15px; }
        .title { font-size: 28px; font-weight: 700; color: #1a1a1a; margin: 0 0 15px 0; line-height: 1.3; }
        .title span { color: #2563eb; }
        .intro-text { color: #64748b; font-size: 15px; line-height: 1.6; margin: 0 0 30px 0; }
        .divider { width: 40px; height: 3px; background-color: #2563eb; margin-bottom: 30px; }
        .card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 25px; background-color: #ffffff; }
        .card-bg { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        .icon-box { width: 40px; height: 40px; background-color: #eff6ff; border-radius: 8px; display: inline-block; text-align: center; line-height: 40px; }
        .btn-outline { border: 1px solid #2563eb; color: #2563eb !important; padding: 8px 20px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 14px; display: inline-block; }
        .btn-primary { background-color: #0b57d0; color: #ffffff !important; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 16px; display: inline-block; }
        .cred-box { background-color: #eff6ff; border-radius: 8px; padding: 15px; width: 100%; box-sizing: border-box; }
        .cred-icon { width: 36px; height: 36px; background-color: #ffffff; border-radius: 8px; text-align: center; line-height: 36px; }
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
        
        $appImagePath = public_path('email_images/application.png');
        $hasAppImage = file_exists($appImagePath);
        
        $companyName = $settings['company_name'] ?? 'Tidcraft';
        $clientName = $tenant->client->name ?? $tenant->business_name;
        
        $productName = $tenant->product ? strtolower($tenant->product->name) : '';
        $isFoodApp = strpos($productName, 'food') !== false;
        $isParkApp = strpos($productName, 'park') !== false;
    @endphp

    <table width="100%" bgcolor="#f4f7f6" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding: 20px;">
                <div class="container">
                    
                    <!-- Header -->
                    <div class="header">
                        <table class="header-table" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="33%">
                                    <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td class="brand-logo-img">
                                                @if($embedLogoPath && isset($message))
                                                    <img src="{{ $message->embed($embedLogoPath) }}" alt="Logo" style="max-width: 36px; max-height: 36px; border-radius: 4px;">
                                                @elseif($logoToUse)
                                                    <img src="{{ asset(ltrim($logoToUse, '/')) }}" alt="Logo" style="max-width: 36px; max-height: 36px; border-radius: 4px;">
                                                @else
                                                    <div style="background-color: #ffffff; color: #0b57d0; width: 36px; height: 36px; border-radius: 8px; text-align: center; line-height: 36px; font-weight: bold; font-size: 20px;">
                                                        {{ strtoupper(substr($companyName, 0, 1)) }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="brand-logo">{{ $companyName }}</div>
                                                <div class="brand-tagline">Technologies</div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td width="34%" class="header-nav">
                                    Build <span>|</span> Innovate <span>|</span> Grow
                                </td>
                                <td width="33%" class="header-motto">
                                    <strong>Technology</strong>
                                    for a Brighter<br>Tomorrow <span style="color:#38bdf8;">&mdash;</span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Content -->
                    <div class="content">
                        
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td valign="top" style="padding-right: 20px;">
                                    <div class="badge">🎉 Good News!</div>
                                    
                                    <h1 class="title">Your Application is Ready,<br><span>{{ $clientName }}</span>!</h1>
                                    
                                    <p class="intro-text">We are excited to let you know that your new application has been successfully provisioned and is now live.</p>
                                    
                                    <div class="divider"></div>
                                </td>
                                @if($hasAppImage && isset($message))
                                <td valign="middle" width="220" align="right">
                                    <img src="{{ $message->embed($appImagePath) }}" alt="Application Ready" style="max-width: 220px; height: auto;">
                                </td>
                                @endif
                            </tr>
                        </table>

                        <!-- Web URL Card -->
                        <div style="background-color: #f5f9ff; border-radius: 12px; padding: 20px; margin-bottom: 15px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="55" valign="middle">
                                        <div class="icon-box" style="background-color: #e5f0ff;">
                                            <img src="https://img.icons8.com/ios-filled/50/2563eb/link--v1.png" width="20" height="20" style="vertical-align: middle;">
                                        </div>
                                    </td>
                                    <td valign="middle">
                                        <div style="font-weight: 700; color: #1a1a1a; margin-bottom: 4px;">Application URL</div>
                                        <a href="{{ $domainUrl }}" style="color: #2563eb; font-weight: 500; text-decoration: underline;">{{ $domainUrl }}</a>
                                    </td>
                                    <td align="right" valign="middle">
                                        <a href="{{ $domainUrl }}" class="btn-outline" style="color: #2563eb !important; background-color: transparent;">Open Application &rarr;</a>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        @if($isFoodApp)
                        <!-- Restaurant Panel Card -->
                        <div style="background-color: #f5f9ff; border-radius: 12px; padding: 20px; margin-bottom: 15px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="55" valign="middle">
                                        <div class="icon-box" style="background-color: #e5f0ff;">
                                            <img src="https://img.icons8.com/ios-filled/50/2563eb/link--v1.png" width="20" height="20" style="vertical-align: middle;">
                                        </div>
                                    </td>
                                    <td valign="middle">
                                        <div style="font-weight: 700; color: #1a1a1a; margin-bottom: 4px;">Restaurant Panel URL</div>
                                        <a href="{{ rtrim($domainUrl, '/') }}/restaurant_panel" style="color: #2563eb; font-weight: 500; text-decoration: underline;">{{ rtrim($domainUrl, '/') }}/restaurant_panel</a>
                                    </td>
                                    <td align="right" valign="middle">
                                        <a href="{{ rtrim($domainUrl, '/') }}/restaurant_panel" class="btn-outline" style="color: #2563eb !important; background-color: transparent;">Open Restaurant Panel &rarr;</a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        @endif

                        @if($isParkApp)
                        <!-- Owner Panel Card -->
                        <div style="background-color: #f5f9ff; border-radius: 12px; padding: 20px; margin-bottom: 15px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="55" valign="middle">
                                        <div class="icon-box" style="background-color: #e5f0ff;">
                                            <img src="https://img.icons8.com/ios-filled/50/2563eb/link--v1.png" width="20" height="20" style="vertical-align: middle;">
                                        </div>
                                    </td>
                                    <td valign="middle">
                                        <div style="font-weight: 700; color: #1a1a1a; margin-bottom: 4px;">Owner Panel URL</div>
                                        <a href="{{ rtrim($domainUrl, '/') }}/owner_panel" style="color: #2563eb; font-weight: 500; text-decoration: underline;">{{ rtrim($domainUrl, '/') }}/owner_panel</a>
                                    </td>
                                    <td align="right" valign="middle">
                                        <a href="{{ rtrim($domainUrl, '/') }}/owner_panel" class="btn-outline" style="color: #2563eb !important; background-color: transparent;">Open Owner Panel &rarr;</a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        @endif

                        <!-- Admin Panel Card -->
                        <div style="background-color: #f5f9ff; border-radius: 12px; padding: 20px; margin-bottom: 25px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="55" valign="middle">
                                        <div class="icon-box" style="background-color: #e5f0ff;">
                                            <img src="https://img.icons8.com/ios-filled/50/2563eb/link--v1.png" width="20" height="20" style="vertical-align: middle;">
                                        </div>
                                    </td>
                                    <td valign="middle">
                                        <div style="font-weight: 700; color: #1a1a1a; margin-bottom: 4px;">Admin Panel URL</div>
                                        <a href="{{ rtrim($domainUrl, '/') }}/admin_panel" style="color: #2563eb; font-weight: 500; text-decoration: underline;">{{ rtrim($domainUrl, '/') }}/admin_panel</a>
                                    </td>
                                    <td align="right" valign="middle">
                                        <a href="{{ rtrim($domainUrl, '/') }}/admin_panel" class="btn-outline" style="color: #2563eb !important; background-color: transparent;">Open Admin Panel &rarr;</a>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Credentials Card -->
                        <div class="card-bg">
                            <table width="100%" style="margin-bottom: 20px;" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="35" valign="top">
                                        <img src="https://img.icons8.com/fluency-systems-regular/48/2563eb/user.png" width="24" height="24">
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; font-size: 18px; color: #1a1a1a; margin-bottom: 4px;">Admin Login Credentials</div>
                                        <div style="color: #64748b; font-size: 14px;">You can use the following credentials to log in to your application's admin panel:</div>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="48%">
                                        <div class="cred-box">
                                            <table width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td width="46" valign="middle">
                                                        <div class="cred-icon">
                                                            <img src="https://img.icons8.com/fluency-systems-regular/48/2563eb/new-post.png" width="20" height="20" style="vertical-align: middle;">
                                                        </div>
                                                    </td>
                                                    <td valign="middle">
                                                        <div style="font-weight: 700; color: #1a1a1a; font-size: 13px; margin-bottom: 2px;">Email</div>
                                                        <a href="mailto:{{ $adminEmail }}" style="color: #2563eb; font-weight: 500; text-decoration: underline;">{{ $adminEmail }}</a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </td>
                                    <td width="4%"></td>
                                    <td width="48%">
                                        <div class="cred-box">
                                            <table width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td width="46" valign="middle">
                                                        <div class="cred-icon">
                                                            <img src="https://img.icons8.com/fluency-systems-regular/48/2563eb/lock.png" width="20" height="20" style="vertical-align: middle;">
                                                        </div>
                                                    </td>
                                                    <td valign="middle">
                                                        <div style="font-weight: 700; color: #1a1a1a; font-size: 13px; margin-bottom: 2px;">Password</div>
                                                        <div style="color: #1a1a1a; font-weight: 600;">{{ $adminPassword }}</div>
                                                    </td>
                                                    <td align="right" valign="middle">
                                                        <img src="https://img.icons8.com/fluency-systems-regular/48/2563eb/copy.png" width="18" height="18" style="opacity: 0.6;">
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            
                            <table width="100%" style="margin-top: 20px;" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="24" valign="top"><img src="https://img.icons8.com/fluency-systems-regular/48/2563eb/info.png" width="16" height="16"></td>
                                    <td style="font-size: 13px; color: #64748b; font-style: italic;">Note: We highly recommend changing your password after your first login for security purposes.</td>
                                </tr>
                            </table>
                        </div>

                        @php
                            $sourceCodeZips = $tenant->product->source_code_zip ?? [];
                            if (is_string($sourceCodeZips)) {
                                $sourceCodeZips = json_decode($sourceCodeZips, true) ?? [];
                            }
                            $setupDoc = $tenant->product->setup_document_pdf ?? null;
                            
                            $attachments = [];
                            foreach((array)$sourceCodeZips as $index => $zip) {
                                $attachments[] = [
                                    'type' => 'ZIP',
                                    'color' => '#f59e0b',
                                    'name' => basename($zip),
                                    'label' => 'Application Source',
                                    'url' => asset('storage/' . $zip)
                                ];
                            }
                            if ($setupDoc) {
                                $attachments[] = [
                                    'type' => 'PDF',
                                    'color' => '#ef4444',
                                    'name' => basename($setupDoc),
                                    'label' => 'Setup Instructions',
                                    'url' => asset('storage/' . $setupDoc)
                                ];
                            }
                            $attachmentCount = count($attachments);
                        @endphp

                        @if($attachmentCount > 0)
                        <!-- Attachments Card -->
                        <div class="card" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 25px;">
                            <table width="100%" style="margin-bottom: 20px;" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="35" valign="top">
                                        <img src="https://img.icons8.com/fluency-systems-regular/48/2563eb/attach.png" width="24" height="24">
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; font-size: 18px; color: #1a1a1a; margin-bottom: 4px;">Important Attachments</div>
                                        <div style="color: #64748b; font-size: 14px;">Please find the necessary files and documents attached for your reference.</div>
                                    </td>
                                    <td align="right" valign="top">
                                        <div style="background-color: #eff6ff; color: #2563eb; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block;">
                                            {{ $attachmentCount }} {{ $attachmentCount > 1 ? 'Attachments' : 'Attachment' }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellspacing="0" cellpadding="0">
                                @foreach(array_chunk($attachments, 2) as $chunk)
                                <tr>
                                    @foreach($chunk as $attachment)
                                    <td width="48%" valign="top" style="padding-bottom: 15px;">
                                        <div style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 10px; text-align: left; position: relative; height: 100%;">
                                            <table width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td width="36" valign="top">
                                                        <div style="width: 28px; height: 36px; background-color: {{ $attachment['color'] }}; border-radius: 4px; display: inline-block; text-align: center; color: #ffffff; font-size: 12px; line-height: 36px; font-weight: bold;">{{ $attachment['type'] }}</div>
                                                    </td>
                                                    <td valign="top" style="padding-left: 8px;">
                                                        <div style="font-size: 11px; font-weight: 700; color: #1a1a1a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;">{{ $attachment['name'] }}</div>
                                                        <div style="font-size: 10px; color: #64748b; margin-top: 2px;">{{ $attachment['label'] }}</div>
                                                    </td>
                                                </tr>
                                            </table>
                                            <div style="text-align: right; margin-top: 10px;">
                                                <a href="{{ $attachment['url'] }}" style="text-decoration: none;" download><img src="https://img.icons8.com/fluency-systems-regular/48/2563eb/download.png" width="16" height="16"></a>
                                            </div>
                                        </div>
                                    </td>
                                    @if($loop->first && count($chunk) == 2)
                                    <td width="4%" style="padding-bottom: 15px;"></td>
                                    @endif
                                    @if($loop->first && count($chunk) == 1)
                                    <td width="4%" style="padding-bottom: 15px;"></td>
                                    <td width="48%" style="padding-bottom: 15px;"></td>
                                    @endif
                                    @endforeach
                                </tr>
                                @endforeach
                            </table>
                        </div>
                        @endif

                        <!-- CTA -->
                        <div style="text-align: center; margin-top: 35px; margin-bottom: 20px;">
                            <a href="{{ $domainUrl }}" class="btn-primary" style="color: #ffffff !important;">Go to your Application &rarr;</a>
                        </div>
                        
                        <div style="text-align: center; color: #64748b; font-size: 13px;">
                            If you have any questions or need support, please open a ticket from your client portal.
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
