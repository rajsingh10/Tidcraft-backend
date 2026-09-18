<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f7f6; font-family: 'Inter', Arial, sans-serif; color: #333333; }
        table { border-spacing: 0; }
        .container { max-width: 650px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); overflow: hidden; }
        .header { background: #002244; padding: 30px 40px; color: #ffffff; }
        .header-table { width: 100%; }
        .brand-logo { font-size: 24px; font-weight: 700; color: #ffffff; text-decoration: none; }
        .brand-logo img { height: 35px; }
        .header-nav { font-size: 12px; color: #e2e8f0; text-align: center; }
        .header-nav span { margin: 0 5px; opacity: 0.5; }
        .header-motto { font-size: 12px; text-align: right; line-height: 1.4; }
        .header-motto strong { display: block; font-size: 13px; font-weight: 600; }
        
        .content { padding: 40px; text-align: center; }
        
        .hero-icon { margin-bottom: 25px; }
        .hero-icon svg { width: 80px; height: 80px; color: #ef4444; }
        
        .title { font-size: 26px; font-weight: 700; margin: 0 0 25px 0; color: #1a1a1a; }
        .title span { color: #ef4444; }
        
        .greeting { font-size: 16px; font-weight: 600; text-align: left; margin: 0 0 10px 0; }
        .message { font-size: 15px; line-height: 1.6; text-align: left; color: #4b5563; margin: 0 0 30px 0; }
        
        .details-box { background-color: #fef2f2; border-radius: 10px; padding: 25px; margin-bottom: 20px; text-align: left; }
        .details-table { width: 100%; font-size: 14px; }
        .details-table td { padding: 8px 0; }
        .details-table td:first-child { font-weight: 600; width: 140px; color: #1f2937; }
        .details-table td:nth-child(2) { width: 15px; }
        .details-table td:last-child { color: #4b5563; }
        .status-badge { color: #ef4444; font-weight: 600; }
        
        .next-steps-box { background-color: #fffbeb; border-radius: 10px; padding: 20px; margin-bottom: 30px; text-align: left; display: flex; }
        .next-steps-icon { width: 40px; height: 40px; background-color: #fef3c7; color: #d97706; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; margin-right: 15px; flex-shrink: 0; }
        .next-steps-text h4 { margin: 0 0 5px 0; font-size: 15px; color: #1e293b; }
        .next-steps-text p { margin: 0; font-size: 13px; line-height: 1.5; color: #475569; }
        
        .action-btn { display: inline-block; background-color: #0055ff; color: #ffffff !important; text-decoration: none; padding: 14px 30px; border-radius: 6px; font-weight: 600; font-size: 15px; text-align: center; margin-bottom: 30px; }
        
        .signoff { text-align: left; margin-top: 30px; font-size: 14px; color: #1e293b; font-weight: 600; line-height: 1.5; }
        
        .footer { padding: 30px 40px; border-top: 1px solid #f1f5f9; background-color: #ffffff; }
        .footer-table { width: 100%; }
        .social-links a { display: inline-block; margin-left: 10px; background: #f1f5f9; width: 32px; height: 32px; border-radius: 50%; text-align: center; line-height: 32px; color: #64748b; text-decoration: none; font-size: 14px; }
        .footer-tagline { font-size: 13px; color: #64748b; margin: 0; line-height: 1.4; }
        .footer-bottom { background-color: #0f172a; color: #94a3b8; font-size: 12px; padding: 20px 40px; text-align: center; }
        .footer-bottom table { width: 100%; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td width="33%">
                        <a href="{{ config('app.url') }}" class="brand-logo">
                            @if(!empty($settings['company_logo']))
                                <img src="{{ url($settings['company_logo']) }}" alt="{{ $settings['company_name'] ?? 'Tidcraft' }}">
                            @else
                                {{ $settings['company_name'] ?? 'Tidcraft' }}
                            @endif
                        </a>
                    </td>
                    <td width="34%" class="header-nav">
                        Build <span>|</span> Innovate <span>|</span> Grow
                    </td>
                    <td width="33%" class="header-motto">
                        Technology<br>
                        <strong>for a Brighter<br>Tomorrow</strong>
                    </td>
                </tr>
            </table>
        </div>

        <div class="content">
            <div class="hero-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            
            <h1 class="title">Payment <span>Failed</span></h1>
            
            <p class="greeting">Hi {{ $tenant->client->name ?? 'Client' }},</p>
            <p class="message">
                Unfortunately, your recent payment attempt was unsuccessful. No funds have been deducted from your account. Please check the details below and try again.
            </p>
            
            <div class="details-box">
                <table class="details-table">
                    <tr>
                        <td>Payment Status</td>
                        <td>:</td>
                        <td class="status-badge">Failed</td>
                    </tr>
                    <tr>
                        <td>Reference Number</td>
                        <td>:</td>
                        <td>{{ $payment->order_id ?? ('REF-' . $payment->id) }}</td>
                    </tr>
                    <tr>
                        <td>Payment Date</td>
                        <td>:</td>
                        <td>{{ $payment->updated_at->format('d F Y, h:i A (T)') }}</td>
                    </tr>
                    <tr>
                        <td>Amount Attempted</td>
                        <td>:</td>
                        <td>{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Payment Method</td>
                        <td>:</td>
                        <td>{{ ucwords($payment->payment_method ?? 'Online Transfer') }}</td>
                    </tr>
                </table>
            </div>

            <div class="next-steps-box">
                <div class="next-steps-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </div>
                <div class="next-steps-text">
                    <h4>What's next?</h4>
                    <p>Your subscription or setup is currently on hold. Please update your payment method or attempt the payment again to continue using our services uninterrupted.</p>
                </div>
            </div>

            <a href="{{ config('app.url') }}/profile" class="action-btn">Retry Payment</a>

            <div class="signoff">
                Warm Regards,<br>
                Team {{ $settings['company_name'] ?? 'Tidcraft' }}
            </div>
        </div>

        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td>
                        @if(!empty($settings['company_logo']))
                            <img src="{{ url($settings['company_logo']) }}" alt="Logo" style="height: 30px;">
                        @else
                            <strong>{{ $settings['company_name'] ?? 'Tidcraft' }}</strong>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <p class="footer-tagline">Let's Build a<br>Brighter Tomorrow Together.</p>
                    </td>
                    <td style="text-align: right;" class="social-links">
                        <a href="#">in</a>
                        <a href="#">tw</a>
                        <a href="#">yt</a>
                        <a href="#">wb</a>
                    </td>
                </tr>
            </table>
        </div>
        <div class="footer-bottom">
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: left;">&copy; {{ date('Y') }} {{ $settings['company_name'] ?? 'Tidcraft' }}. All rights reserved.</td>
                    <td style="text-align: right;">www.tidcraft.com</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
