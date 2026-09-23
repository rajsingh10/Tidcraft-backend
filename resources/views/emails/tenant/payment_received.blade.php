<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Received</title>
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
        .hero-img-container { text-align: center; margin-bottom: 25px; }
        .hero-img { max-width: 200px; height: auto; }
        
        .title { font-size: 26px; font-weight: 700; margin: 0 0 25px 0; color: #1a1a1a; }
        .title span { color: #16a34a; }
        
        .greeting { font-size: 16px; font-weight: 600; text-align: left; margin: 0 0 10px 0; }
        .message { font-size: 15px; line-height: 1.6; text-align: left; color: #4b5563; margin: 0 0 30px 0; }
        
        .details-box { background-color: #f0fdf4; border-radius: 10px; padding: 25px; margin-bottom: 20px; text-align: left; }
        .details-table { width: 100%; font-size: 14px; }
        .details-table td { padding: 8px 0; }
        .details-table td:first-child { font-weight: 600; width: 140px; color: #1f2937; }
        .details-table td:nth-child(2) { width: 15px; }
        .details-table td:last-child { color: #4b5563; }
        .status-badge { color: #16a34a; font-weight: 600; }
        
        .attachment-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 20px; text-align: left; }
        .attachment-header { display: flex; align-items: center; margin-bottom: 15px; }
        .attachment-icon { width: 40px; height: 40px; background-color: #eff6ff; color: #3b82f6; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; margin-right: 15px; vertical-align: top; }
        .attachment-text h4 { margin: 0 0 5px 0; font-size: 15px; color: #1e293b; }
        .attachment-text p { margin: 0; font-size: 13px; color: #64748b; }
        .file-box { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; display: flex; align-items: center; justify-content: space-between; }
        .file-info { display: flex; align-items: center; }
        .pdf-icon { width: 32px; height: 32px; background-color: #ef4444; color: #ffffff; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; margin-right: 12px; }
        .file-name { font-size: 14px; font-weight: 600; color: #1e293b; margin: 0 0 2px 0; }
        .file-size { font-size: 12px; color: #94a3b8; margin: 0; }
        .download-btn { color: #3b82f6; text-decoration: none; padding: 5px; }
        
        .next-steps-box { background-color: #f0f7ff; border-radius: 10px; padding: 20px; margin-bottom: 30px; text-align: left; display: flex; }
        .next-steps-icon { width: 40px; height: 40px; background-color: #e0f2fe; color: #0284c7; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; margin-right: 15px; flex-shrink: 0; }
        .next-steps-text h4 { margin: 0 0 5px 0; font-size: 15px; color: #1e293b; }
        .next-steps-text p { margin: 0; font-size: 13px; line-height: 1.5; color: #475569; }
        
        .action-btn { display: inline-block; background-color: #0055ff; color: #ffffff !important; text-decoration: none; padding: 14px 30px; border-radius: 6px; font-weight: 600; font-size: 15px; text-align: center; margin-bottom: 30px; }
        
        .thank-you-box { background-color: #f0fdf4; border-radius: 8px; padding: 15px 20px; display: flex; align-items: center; text-align: left; }
        .heart-icon { color: #22c55e; font-size: 24px; margin-right: 15px; }
        .thank-you-text h4 { margin: 0 0 2px 0; font-size: 14px; color: #166534; }
        .thank-you-text p { margin: 0; font-size: 13px; color: #15803d; }
        
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
            <div class="hero-img-container">
                <img src="{{ url('email_images/invoice.png') }}" alt="Invoice Success" class="hero-img">
            </div>
            
            <h1 class="title">Payment Received <span>Successfully!</span></h1>
            
            <p class="greeting">Hi {{ $tenant->client->name ?? 'Client' }},</p>
            <p class="message">
                Thank you for your payment. We have successfully received your payment for the services. Your trust in {{ $settings['company_name'] ?? 'Tidcraft' }} means a lot to us.
            </p>
            
            <!-- Details Box -->
            <div class="details-box">
                <table class="details-table">
                    <tr>
                        <td>Payment Status</td>
                        <td>:</td>
                        <td class="status-badge">Received</td>
                    </tr>
                    <tr>
                        <td>Invoice Number</td>
                        <td>:</td>
                        <td>{{ $payment->order_id ?? ('INV-' . $payment->id) }}</td>
                    </tr>
                    <tr>
                        <td>Payment Date</td>
                        <td>:</td>
                        <td>{{ $payment->created_at->format('d F Y, h:i A (T)') }}</td>
                    </tr>
                    <tr>
                        <td>Amount Paid</td>
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


            <!-- Next Steps -->
            <div class="next-steps-box">
                <div class="next-steps-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path></svg>
                </div>
                <div class="next-steps-text">
                    <h4>What Happens Next?</h4>
                    <p>Our team will review your payment and process the next steps. We will contact you within 24 hours to confirm and proceed further. If you have any questions, feel free to reach out to us anytime.</p>
                </div>
            </div>

            <a href="{{ config('app.url') }}/profile" class="action-btn">Go to Your Dashboard &rarr;</a>

            <!-- Thank You -->
            <div class="thank-you-box">
                <div class="heart-icon">♥</div>
                <div class="thank-you-text">
                    <h4>Thank you for choosing {{ $settings['company_name'] ?? 'Tidcraft' }}!</h4>
                    <p>We look forward to a long and successful partnership with you.</p>
                </div>
            </div>
            
            <div class="signoff">
                Warm Regards,<br>
                Team {{ $settings['company_name'] ?? 'Tidcraft' }}
            </div>
        </div>

        <!-- Footer -->
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
