<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Website Setup Is Ready!</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f8fafc; font-family: 'Inter', Arial, sans-serif; color: #334155; }
        table { border-spacing: 0; border-collapse: collapse; }
        td { padding: 0; }
        
        .container { max-width: 650px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); overflow: hidden; }
        
        .header { padding: 30px 40px; background-color: #ffffff; }
        .header-table { width: 100%; }
        .brand-logo img { height: 35px; }
        .header-nav { font-size: 13px; color: #64748b; text-align: right; }
        .header-nav span { margin: 0 5px; opacity: 0.5; }
        
        .content { padding: 0 40px 40px 40px; }
        
        .hero-section { width: 100%; margin-bottom: 30px; }
        .hero-text { vertical-align: middle; padding-right: 20px; }
        .greeting { font-size: 16px; color: #475569; margin: 0 0 5px 0; }
        .hero-title { font-size: 32px; font-weight: 800; color: #0f172a; line-height: 1.15; margin: 0 0 15px 0; }
        .hero-title span { color: #2563eb; }
        .hero-desc { font-size: 14px; line-height: 1.6; color: #475569; margin: 0 0 15px 0; }
        .hero-image-cell { vertical-align: middle; text-align: center; width: 250px; }
        .hero-image { max-width: 250px; height: auto; }
        
        .steps-header { background-color: #f1f5f9; border-radius: 8px; padding: 15px 20px; margin-bottom: 20px; }
        .steps-header-table { width: 100%; }
        .sh-icon { width: 40px; height: 40px; background-color: #2563eb; border-radius: 50%; color: #fff; text-align: center; vertical-align: middle; }
        .sh-text { padding-left: 15px; font-weight: 700; color: #0f172a; font-size: 15px; }
        .sh-action { text-align: right; font-size: 13px; color: #64748b; }
        .sh-action-btn { display: inline-block; padding: 6px 12px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; color: #475569; text-decoration: none; font-weight: 500; }
        
        .step-row { padding: 20px 0; border-bottom: 1px solid #f1f5f9; }
        .step-table { width: 100%; }
        .step-num { width: 40px; height: 40px; border-radius: 50%; background-color: #f1f5f9; color: #3b82f6; font-size: 18px; font-weight: 700; text-align: center; line-height: 40px; vertical-align: top; }
        .step-icon { width: 50px; height: 50px; border-radius: 12px; text-align: center; line-height: 50px; vertical-align: top; padding-left: 15px; }
        .step-content { padding-left: 15px; vertical-align: top; padding-top: 5px; }
        .step-title { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 5px 0; }
        .step-desc { font-size: 13px; line-height: 1.5; color: #64748b; margin: 0; }
        .step-action { vertical-align: top; text-align: right; width: 180px; padding-top: 5px; }
        .step-btn { display: inline-block; background-color: #2563eb; color: #ffffff !important; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; width: 140px; text-align: center; }
        .step-link { display: block; font-size: 10px; color: #94a3b8; margin-top: 5px; text-decoration: none; word-break: break-all; width: 150px; margin-left: auto; text-align: center; }
        
        .success-box { background-color: #f0fdf4; border-radius: 10px; padding: 20px 25px; margin-top: 30px; margin-bottom: 20px; }
        .success-table { width: 100%; }
        .success-icon { width: 50px; vertical-align: top; }
        .success-icon div { width: 36px; height: 36px; background-color: #22c55e; border-radius: 50%; color: #fff; text-align: center; line-height: 36px; font-weight: bold; font-size: 18px; }
        .success-text { vertical-align: top; }
        .success-text h4 { margin: 0 0 5px 0; color: #166534; font-size: 16px; }
        .success-text p { margin: 0; color: #15803d; font-size: 13px; line-height: 1.5; }
        
        .help-box { background-color: #eff6ff; border-radius: 10px; padding: 20px 25px; margin-bottom: 30px; }
        .help-table { width: 100%; }
        .help-icon { width: 50px; vertical-align: top; }
        .help-icon div { width: 36px; height: 36px; background-color: #dbeafe; color: #2563eb; border-radius: 50%; text-align: center; line-height: 36px; }
        .help-text { vertical-align: top; }
        .help-text h4 { margin: 0 0 5px 0; color: #1e3a8a; font-size: 15px; }
        .help-text p { margin: 0; color: #1e40af; font-size: 13px; line-height: 1.5; }
        .help-action { vertical-align: middle; text-align: right; width: 140px; border-left: 1px solid #bfdbfe; padding-left: 15px; }
        .help-action h5 { margin: 0 0 8px 0; color: #1e3a8a; font-size: 12px; }
        .help-btn { display: inline-block; background-color: #2563eb; color: #ffffff !important; text-decoration: none; padding: 8px 15px; border-radius: 6px; font-size: 13px; font-weight: 600; text-align: center; }
        
        .footer { padding: 30px 40px; background-color: #ffffff; border-top: 1px solid #f1f5f9; }
        .footer-table { width: 100%; }
        .footer-content h4 { margin: 0 0 5px 0; color: #0f172a; font-size: 15px; }
        .footer-content p { margin: 0 0 20px 0; color: #64748b; font-size: 13px; }
        .social-links a { display: inline-block; margin-right: 10px; background: #f1f5f9; width: 32px; height: 32px; border-radius: 50%; text-align: center; line-height: 32px; color: #64748b; text-decoration: none; font-size: 14px; }
        .signoff { font-size: 13px; color: #64748b; line-height: 1.5; }
        .signoff strong { color: #0f172a; }
        
        /* Step specific icons */
        .icon-bg-0 { background-color: #e0f2fe; color: #0284c7; }
        .icon-bg-1 { background-color: #dcfce7; color: #166534; }
        .icon-bg-2 { background-color: #ffedd5; color: #9a3412; }
        .icon-bg-3 { background-color: #f3e8ff; color: #6b21a8; }
        .icon-bg-4 { background-color: #ffe4e6; color: #be123c; }
    </style>
</head>
<body>
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

        <div class="content">
            <!-- Hero Section -->
            <table class="hero-section">
                <tr>
                    <td class="hero-text">
                        <p class="greeting">Hi {{ $tenant->client->name ?? 'Client' }},</p>
                        <h1 class="hero-title">Your Website Setup<br><span>Is Ready!</span></h1>
                        <p class="hero-desc">
                            We're excited to let you know that your application has been successfully deployed. To complete the setup and make your website fully functional, please follow the steps below.
                        </p>
                        <p class="hero-desc">
                            These settings will help you customize your site, enable payments, and manage your content easily from the <strong>Admin Panel</strong>.
                        </p>
                    </td>
                    <td class="hero-image-cell">
                        <img src="{{ url('email_images/setup.png') }}" alt="Setup Ready" class="hero-image">
                    </td>
                </tr>
            </table>

            <!-- Steps Header -->
            <div class="steps-header">
                <table class="steps-header-table">
                    <tr>
                        <td width="40" class="sh-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-top: 10px;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                        </td>
                        <td class="sh-text">
                            Follow the steps below to complete your website setup
                        </td>
                        <td class="sh-action">
                            <a href="https://{{ $tenant->domain->domain ?? 'yourdomain.com' }}/admin" class="sh-action-btn">Access your Admin Panel anytime &rarr;</a>
                        </td>
                    </tr>
                </table>
            </div>

            @php
                $adminUrl = 'https://' . ($tenant->domain->domain ?? 'yourdomain.com');
                $productName = strtolower($tenant->product->name ?? '');
                
                $icons = [
                    '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
                    '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><path d="M12 2c2.8 0 5 2.2 5 5s-2.2 5-5 5-5-2.2-5-5 2.2-5 5-5z"></path><path d="M12 14c-4.4 0-8 3.6-8 8h16c0-4.4-3.6-8-8-8z"></path><path d="M17.5 4.5l-4.5 4.5"></path></svg>',
                    '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>',
                    '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><rect x="2" y="5" width="20" height="14" rx="2" ry="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>',
                    '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>'
                ];

                if (str_contains($productName, 'park')) {
                    $steps = [
                        [
                            'title' => 'Set Up Your Site Content',
                            'desc' => 'Review and update your website content, including pages, text, and other CMS information.',
                            'link' => '/admin_panel/cms',
                            'btn_text' => 'Open CMS Settings'
                        ],
                        [
                            'title' => 'Set Up Your Payment Method',
                            'desc' => 'Configure your preferred payment gateway and add the required payment credentials.',
                            'link' => '/admin_panel/settings/payments/stripe',
                            'btn_text' => 'Open Payment Settings'
                        ],
                        [
                            'title' => 'Set Up Global Settings',
                            'desc' => 'Configure your global website settings, contact information, and other basic preferences.',
                            'link' => '/admin_panel/settings/globals',
                            'btn_text' => 'Open Global Settings'
                        ],
                        [
                            'title' => 'Set Up Landing Page',
                            'desc' => 'Configure your landing page template and design preferences.',
                            'link' => '/admin_panel/settings/landingPageTemplate',
                            'btn_text' => 'Open Landing Page Setup'
                        ],
                        [
                            'title' => 'Set Up Your Region & Currency',
                            'desc' => 'Select your appropriate country/region, currency, timezone, and related regional settings.',
                            'link' => '/admin_panel/currency',
                            'btn_text' => 'Open Currency Setup'
                        ]
                    ];
                } else {
                    // Default / Food App Flow
                    $steps = [
                        [
                            'title' => 'Set Up Your Site Settings',
                            'desc' => 'Configure your general website settings, contact information, and other basic preferences.',
                            'link' => '/admin_panel/settings',
                            'btn_text' => 'Open Site Settings'
                        ],
                        [
                            'title' => 'Set Up Your Site Branding',
                            'desc' => 'Add or update your logo, favicon, brand colors, and other branding details.',
                            'link' => '/admin_panel/settings/branding',
                            'btn_text' => 'Open Branding Settings'
                        ],
                        [
                            'title' => 'Set Up Your Region & Currency',
                            'desc' => 'Select your appropriate country/region, currency, timezone, and related regional settings.',
                            'link' => '/admin_panel/payment/stripe',
                            'btn_text' => 'Open Currency Setup'
                        ],
                        [
                            'title' => 'Set Up Your Payment Method',
                            'desc' => 'Configure your preferred payment gateway and add the required payment credentials.',
                            'link' => '/admin_panel/payment/stripe',
                            'btn_text' => 'Open Payment Settings'
                        ],
                        [
                            'title' => 'Set Up Your Site Content',
                            'desc' => 'Review and update your website content, including pages, text, and other CMS information.',
                            'link' => '/admin_panel/cms',
                            'btn_text' => 'Open CMS Settings'
                        ]
                    ];
                }
            @endphp

            @foreach($steps as $index => $step)
            <div class="step-row" {!! $loop->last ? 'style="border-bottom: none;"' : '' !!}>
                <table class="step-table">
                    <tr>
                        <td width="40">
                            <div class="step-num">{{ $index + 1 }}</div>
                        </td>
                        <td width="60" class="step-icon">
                            <div style="width: 45px; height: 45px; border-radius: 10px; text-align: center; line-height: 45px;" class="icon-bg-{{ $index }}">
                                {!! $icons[$index % count($icons)] !!}
                            </div>
                        </td>
                        <td class="step-content">
                            <h4 class="step-title">{{ $step['title'] }}</h4>
                            <p class="step-desc">{{ $step['desc'] }}</p>
                        </td>
                        <td class="step-action">
                            <a href="{{ $adminUrl }}{{ $step['link'] }}" class="step-btn">{{ $step['btn_text'] }} &rarr;</a>
                            <a href="{{ $adminUrl }}{{ $step['link'] }}" class="step-link">{{ $adminUrl }}{{ $step['link'] }}</a>
                        </td>
                    </tr>
                </table>
            </div>
            @endforeach

            <!-- Success Box -->
            <div class="success-box">
                <table class="success-table">
                    <tr>
                        <td class="success-icon">
                            <div>&#10003;</div>
                        </td>
                        <td class="success-text">
                            <h4>Almost There!</h4>
                            <p>Once you have completed these steps, please let our team know. We will review the configuration and assist you with any remaining requirements.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Help Box -->
            <div class="help-box">
                <table class="help-table">
                    <tr>
                        <td class="help-icon">
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-top: 8px;"><path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path></svg>
                            </div>
                        </td>
                        <td class="help-text">
                            <h4>Need Help?</h4>
                            <p>If you have any questions while completing the setup, please contact our support team. We'll be happy to assist you and ensure everything is configured correctly.</p>
                        </td>
                        <td class="help-action">
                            <h5>We're Here<br>for You!</h5>
                            <a href="mailto:support@tidcraft.com" class="help-btn">Contact Support &rarr;</a>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td>
                        <div class="footer-content">
                            <h4>Thank you for choosing {{ $settings['company_name'] ?? 'Tidcraft Technologies' }}.</h4>
                            <p>We appreciate your trust and look forward to helping you build a successful digital experience.</p>
                        </div>
                        <div class="signoff">
                            Warm regards,<br>
                            <strong>Team {{ $settings['company_name'] ?? 'Tidcraft Technologies' }}</strong><br>
                            <i style="font-size: 11px;">Tech for a Brighter Tomorrow</i>
                        </div>
                    </td>
                    <td style="vertical-align: top; text-align: right;">
                        <div class="social-links" style="margin-top: 10px;">
                            <a href="#">in</a>
                            <a href="#">tw</a>
                            <a href="#">yt</a>
                            <a href="#">wb</a>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
