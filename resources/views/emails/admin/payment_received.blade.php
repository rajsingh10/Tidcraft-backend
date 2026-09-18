<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Payment Received</title>
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
        .hero-img { max-width: 250px; height: auto; }
        
        .title { font-size: 26px; font-weight: 700; margin: 0 0 25px 0; color: #0f172a; }
        
        .greeting { font-size: 16px; font-weight: 600; text-align: left; margin: 0 0 10px 0; color: #1e293b; }
        .message { font-size: 15px; line-height: 1.6; text-align: left; color: #475569; margin: 0 0 30px 0; }
        
        .details-box { background-color: #f8fafc; border-radius: 10px; padding: 25px; margin-bottom: 25px; display: flex; align-items: center; text-align: left; border: 1px solid #e2e8f0; }
        .details-icon { width: 60px; height: 60px; background-color: #e0f2fe; color: #0ea5e9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 25px; flex-shrink: 0; }
        .details-icon svg { width: 30px; height: 30px; }
        .details-table { width: 100%; font-size: 13px; }
        .details-table td { padding: 6px 0; }
        .details-table td:first-child { font-weight: 600; width: 130px; color: #0f172a; }
        .details-table td:nth-child(2) { width: 15px; color: #0f172a; }
        .details-table td:last-child { color: #334155; }
        .details-link { color: #2563eb; text-decoration: none; }
        
        .action-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 20px; text-align: left; display: flex; align-items: flex-start; }
        .action-icon { width: 40px; height: 40px; background-color: #e0e7ff; color: #4f46e5; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; margin-right: 15px; flex-shrink: 0; }
        .action-text { flex-grow: 1; }
        .action-text h4 { margin: 0 0 5px 0; font-size: 15px; color: #1e293b; }
        .action-text p { margin: 0 0 15px 0; font-size: 13px; line-height: 1.5; color: #64748b; }
        .action-btn { display: inline-block; background-color: #0055ff; color: #ffffff !important; text-decoration: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; font-size: 14px; text-align: center; }
        
        .note-box { background-color: #f1f5f9; border-radius: 10px; padding: 20px; margin-bottom: 30px; text-align: left; display: flex; align-items: flex-start; }
        .note-icon { width: 40px; height: 40px; background-color: #0055ff; color: #ffffff; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-right: 15px; flex-shrink: 0; font-weight: bold; }
        .note-text h4 { margin: 0 0 5px 0; font-size: 15px; color: #0f172a; }
        .note-text p { margin: 0; font-size: 13px; line-height: 1.5; color: #475569; }
        
        .signoff { text-align: left; font-size: 14px; color: #475569; line-height: 1.5; }
        .signoff strong { color: #0f172a; }
        
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

        <!-- Content -->
        <div class="content">
            <div class="hero-img-container">
                <img src="{{ url('email_images/payment_receive.png') }}" alt="New Payment Received" class="hero-img">
            </div>
            
            <h1 class="title">New Payment Received!</h1>
            
            <p class="greeting">Hello Admin,</p>
            <p class="message">
                A new payment has been received from a client. Please find the payment details below.
            </p>
            
            <!-- Details Box -->
            <div class="details-box">
                <div class="details-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" />
                    </svg>
                </div>
                <div style="flex-grow: 1;">
                    <table class="details-table">
                        <tr>
                            <td>Client Name</td>
                            <td>:</td>
                            <td>{{ $tenant->client->name ?? $tenant->business_name }}</td>
                        </tr>
                        <tr>
                            <td>Client Email</td>
                            <td>:</td>
                            <td><a href="mailto:{{ $tenant->primary_contact_email }}" class="details-link">{{ $tenant->primary_contact_email }}</a></td>
                        </tr>
                        <tr>
                            <td>Invoice Number</td>
                            <td>:</td>
                            <td>{{ $payment->order_id ?? ('INV-' . $payment->id) }}</td>
                        </tr>
                        <tr>
                            <td>Amount Paid</td>
                            <td>:</td>
                            <td>{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Payment Date</td>
                            <td>:</td>
                            <td>{{ $payment->updated_at->format('d F Y, h:i A (T)') }}</td>
                        </tr>
                        <tr>
                            <td>Payment Method</td>
                            <td>:</td>
                            <td>{{ ucwords($payment->payment_method ?? 'Online Transfer') }}</td>
                        </tr>
                        <tr>
                            <td>Transaction ID</td>
                            <td>:</td>
                            <td>{{ $payment->transaction_id ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Actions Box -->
            <div class="action-box">
                <div class="action-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </div>
                <div class="action-text">
                    <h4>Actions</h4>
                    <p>You can view the client details, invoice, and payment information from the admin panel.</p>
                    <a href="{{ config('app.url') }}/admin/payments" class="action-btn">View in Admin Panel &rarr;</a>
                </div>
            </div>

            <!-- Note -->
            <div class="note-box">
                <div class="note-icon">i</div>
                <div class="note-text">
                    <h4>Note</h4>
                    <p>This is an automated notification. Please verify the payment in the system. If you find any discrepancy, kindly check with the client or payment gateway.</p>
                </div>
            </div>
            
            <div class="signoff">
                Regards,<br>
                <strong>Tidcraft System</strong>
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
