<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Client Registered - Tidcraft</title>
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
        .hero-title {
            color: #333333;
            font-size: 28px;
            margin: 0 0 10px 0;
        }
        .hero-title span {
            color: #0d6efd;
        }
        .hero-subtitle {
            color: #555555;
            font-size: 14px;
            margin: 0;
            line-height: 1.5;
        }
        .details-box {
            margin: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
        }
        .detail-item {
            padding: 10px;
            vertical-align: top;
            width: 50%;
        }
        .detail-icon-wrap {
            display: inline-block;
            background-color: #f0f6ff;
            color: #0d6efd;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            text-align: center;
            line-height: 36px;
            font-size: 18px;
            margin-right: 10px;
            vertical-align: top;
        }
        .detail-content {
            display: inline-block;
            vertical-align: top;
        }
        .detail-label {
            color: #333333;
            font-size: 11px;
            font-weight: bold;
            margin: 0 0 4px 0;
        }
        .detail-value {
            color: #555555;
            font-size: 13px;
            margin: 0;
        }
        .success-box {
            background-color: #e6f8ec;
            border-radius: 8px;
            margin: 20px;
            padding: 20px;
            display: table;
            width: calc(100% - 40px);
        }
        .success-icon-col {
            display: table-cell;
            width: 50px;
            vertical-align: middle;
        }
        .success-icon {
            background-color: #198754;
            color: #ffffff;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-block;
            line-height: 36px;
            text-align: center;
            font-size: 18px;
        }
        .success-content {
            display: table-cell;
            vertical-align: middle;
        }
        .success-title {
            color: #198754;
            font-weight: bold;
            font-size: 14px;
            margin: 0 0 5px 0;
        }
        .success-text {
            color: #444444;
            font-size: 12px;
            margin: 0;
        }
        .btn {
            display: block;
            background-color: #0d6efd;
            color: #ffffff;
            text-decoration: none;
            padding: 14px 24px;
            border-radius: 6px;
            font-weight: bold;
            text-align: center;
            margin: 20px;
            font-size: 15px;
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
        .automated-box {
            background-color: #f1f5f9;
            color: #64748b;
            font-size: 11px;
            text-align: center;
            padding: 12px;
            margin: 0 20px 20px 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div style="padding: 20px 0; background-color: #f4f6f9;">
        <div class="email-container">
            
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

            <!-- Hero Section -->
            <div class="hero">
                <div style="font-size: 70px; margin-bottom: 10px; line-height: 1; color:#0d6efd;">👤+</div>
                <h1 class="hero-title"><span>New Client</span> Registered</h1>
                <p class="hero-subtitle">A new client has successfully registered on {{ $settings['company_name'] ?? 'Tidcraft' }}.<br>Here are the details:</p>
            </div>

            <!-- Details Box -->
            <div class="details-box">
                <table class="details-table" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="detail-item">
                            <div class="detail-icon-wrap">👤</div>
                            <div class="detail-content">
                                <p class="detail-label">Client Name</p>
                                <p class="detail-value">{{ $user->name }}</p>
                            </div>
                        </td>
                        <td class="detail-item">
                            <div class="detail-icon-wrap">✉️</div>
                            <div class="detail-content">
                                <p class="detail-label">Email Address</p>
                                <p class="detail-value">{{ $user->email }}</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="detail-item" style="padding-top: 15px;">
                            <div class="detail-icon-wrap">📞</div>
                            <div class="detail-content">
                                <p class="detail-label">Phone Number</p>
                                <p class="detail-value">{{ $user->contact ?? 'N/A' }}</p>
                            </div>
                        </td>
                        <td class="detail-item" style="padding-top: 15px;">
                            <div class="detail-icon-wrap">🏢</div>
                            <div class="detail-content">
                                <p class="detail-label">Company Name</p>
                                <p class="detail-value">{{ $user->company_name ?? 'N/A' }}</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="detail-item" style="padding-top: 15px;">
                            <div class="detail-icon-wrap">📅</div>
                            <div class="detail-content">
                                <p class="detail-label">Registration Date & Time</p>
                                <p class="detail-value">{{ \Carbon\Carbon::parse($user->created_at)->format('d F Y, h:i A (T)') }}</p>
                            </div>
                        </td>
                        <td class="detail-item" style="padding-top: 15px;">
                            <div class="detail-icon-wrap">📍</div>
                            <div class="detail-content">
                                <p class="detail-label">Location</p>
                                <p class="detail-value">{{ $user->location ?? 'N/A' }}</p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Success Box -->
            <table class="success-box" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="success-icon-col">
                        <div class="success-icon">✓</div>
                    </td>
                    <td class="success-content">
                        <p class="success-title">The client account has been created successfully.</p>
                        <p class="success-text">You can view and manage the client from your admin panel.</p>
                    </td>
                </tr>
            </table>

            <!-- Admin Action Button -->
            <a href="{{ config('app.url') }}/admin" class="btn" style="color: #ffffff;">Go to Admin Panel &rarr;</a>

            <!-- Help Box -->
            <table class="help-box" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="help-icon-col">
                        <div class="help-icon">💡</div>
                    </td>
                    <td class="help-content">
                        <p class="help-title">Need Help?</p>
                        <p class="help-text">If you have any questions, feel free to contact our support team.</p>
                        <a href="{{ config('app.url') }}/support" class="btn-outline" style="color: #0d6efd;">Contact Support &rarr;</a>
                    </td>
                </tr>
            </table>

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

            <!-- Automated Email Disclaimer -->
            <div class="automated-box">
                This is an automated email. Please do not reply to this email.
            </div>

        </div>
    </div>
</body>
</html>
