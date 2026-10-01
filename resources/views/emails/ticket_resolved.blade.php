<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Resolved</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f7f6;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #333333;
        }
        .container {
            max-width: 650px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #0e2a53 0%, #1e4a8b 100%);
            padding: 30px 40px;
            color: #ffffff;
            position: relative;
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
            vertical-align: middle;
            text-align: right;
            font-size: 13px;
            opacity: 0.8;
            line-height: 1.5;
        }
        .brand-logo {
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .brand-logo img {
            width: 32px;
            height: 32px;
        }
        .brand-tagline {
            font-size: 12px;
            opacity: 0.7;
            margin-top: 4px;
        }
        .shield-icon-container {
            text-align: center;
            margin-top: -35px;
            position: relative;
            z-index: 10;
        }
        .shield-icon {
            background-color: #ffffff;
            padding: 10px;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .shield-icon div {
            background-color: #137333;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            text-align: center;
            line-height: 50px;
            color: white;
        }
        .shield-icon div img {
            vertical-align: middle;
        }
        .shield-icon svg {
            width: 24px;
            height: 24px;
            fill: currentColor;
            vertical-align: middle;
        }
        .content {
            padding: 30px 40px;
            text-align: center;
        }
        .title {
            font-size: 26px;
            font-weight: 700;
            margin: 10px 0;
            color: #1a1a1a;
        }
        .title span {
            color: #137333;
        }
        .subtitle {
            font-size: 14px;
            color: #666666;
            margin-bottom: 30px;
        }
        .details-grid {
            background-color: #ffffff;
            border: 1px solid #eaeaea;
            border-radius: 8px;
            text-align: left;
            width: 100%;
            border-collapse: collapse;
        }
        .details-grid td {
            padding: 20px;
            vertical-align: top;
            width: 50%;
            border-bottom: 1px solid #eaeaea;
        }
        .details-grid td:first-child {
            border-right: 1px solid #eaeaea;
        }
        .details-grid tr:last-child td {
            border-bottom: none;
        }
        .detail-item {
            display: table;
            width: 100%;
        }
        .detail-icon {
            display: table-cell;
            width: 40px;
            vertical-align: top;
            padding-top: 2px;
        }
        .detail-icon div {
            background-color: #f0f4fa;
            color: #0b57d0;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            text-align: center;
            line-height: 32px;
        }
        .detail-icon div img {
            vertical-align: middle;
        }
        .detail-icon svg {
            width: 16px;
            height: 16px;
            fill: currentColor;
            vertical-align: middle;
        }
        .detail-text {
            display: table-cell;
            vertical-align: top;
        }
        .detail-label {
            font-size: 12px;
            color: #666666;
            margin-bottom: 4px;
        }
        .detail-value {
            font-size: 14px;
            font-weight: 600;
            color: #333333;
        }
        .btn {
            background-color: #0b57d0;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            display: inline-block;
        }
        .alert-box {
            margin-top: 25px;
            background-color: #e8f0fe;
            border-radius: 8px;
            padding: 15px 20px;
            display: table;
            width: 100%;
            box-sizing: border-box;
            text-align: left;
        }
        .alert-icon {
            display: table-cell;
            width: 35px;
            vertical-align: middle;
        }
        .alert-text {
            display: table-cell;
            vertical-align: middle;
            font-size: 13px;
            color: #0b57d0;
            line-height: 1.5;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #eaeaea;
            padding-top: 20px;
            display: table;
            width: 100%;
        }
        .footer-left {
            display: table-cell;
            vertical-align: middle;
            text-align: left;
        }
        .footer-logo {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 4px;
        }
        .copyright {
            font-size: 12px;
            color: #888888;
        }
        .footer-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
        }
        .social-icons a {
            color: #888888;
            text-decoration: none;
            margin-left: 10px;
            font-size: 16px;
        }
        .footer-tagline {
            font-size: 11px;
            color: #888888;
            margin-top: 8px;
        }
        .automated-text {
            font-size: 12px;
            color: #999999;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        @php
            $companyName = \App\Models\Setting::where('key', 'company_name')->value('value') ?? 'TidCraft';
            $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        @endphp

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

        <!-- Shield Icon for Success -->
        <div class="shield-icon-container">
            <div class="shield-icon">
                <div>
                    <img src="https://img.icons8.com/ios-filled/50/ffffff/ok--v1.png" alt="Resolved" style="width: 24px; height: 24px;">
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="title">Ticket <span>Resolved</span></div>
            <div class="subtitle">Hello {{ $user->name ?? 'Customer' }}, your support ticket has been marked as resolved. Here are the details:</div>

            <table class="details-grid">
                <!-- Row 1 -->
                <tr>
                    <td>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <div><img src="https://img.icons8.com/ios-filled/50/0b57d0/hashtag.png" alt="ID" style="width: 16px; height: 16px;"></div>
                            </div>
                            <div class="detail-text">
                                <div class="detail-label">Ticket ID</div>
                                <div class="detail-value">#{{ $ticket->ticket_id ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <div><img src="https://img.icons8.com/ios-filled/50/0b57d0/document.png" alt="Subject" style="width: 16px; height: 16px;"></div>
                            </div>
                            <div class="detail-text">
                                <div class="detail-label">Subject</div>
                                <div class="detail-value">{{ $ticket->subject ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </td>
                </tr>
                <!-- Row 2 -->
                <tr>
                    <td>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <div><img src="https://img.icons8.com/ios-filled/50/0b57d0/ok.png" alt="Status" style="width: 16px; height: 16px;"></div>
                            </div>
                            <div class="detail-text">
                                <div class="detail-label">Status</div>
                                <div class="detail-value" style="color: #137333;">Resolved</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <div><img src="https://img.icons8.com/ios-filled/50/0b57d0/calendar.png" alt="Date" style="width: 16px; height: 16px;"></div>
                            </div>
                            <div class="detail-text">
                                <div class="detail-label">Date Resolved</div>
                                <div class="detail-value">{{ now()->format('d F Y, h:i A') }} (UTC)</div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="alert-box">
                <div class="alert-icon">
                    <img src="https://img.icons8.com/ios-filled/50/0b57d0/info.png" alt="Info" style="width: 24px; height: 24px;">
                </div>
                <div class="alert-text">
                    If you still have issues related to this ticket, please reply to this email or open a new support ticket in your dashboard.
                </div>
            </div>

            <div style="margin-top: 30px;">
                <a href="{{ config('app.url') }}/support/tickets/{{ $ticket->ticket_id ?? '' }}" class="btn">View Ticket Details</a>
            </div>

            <div class="automated-text">
                This is an automated email. Please do not reply to this email unless you have further questions regarding this ticket.
            </div>

            <div class="footer">
                <div class="footer-left">
                    <div class="footer-logo">{{ $companyName }}</div>
                    <div class="copyright">&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</div>
                </div>
                <div class="footer-right">
                    <div class="social-icons">
                        <a href="#"><img src="https://img.icons8.com/ios-filled/24/888888/linkedin.png" alt="in" style="width: 16px; height: 16px; vertical-align: middle;"></a>
                        <a href="#"><img src="https://img.icons8.com/ios-filled/24/888888/twitter.png" alt="tw" style="width: 16px; height: 16px; vertical-align: middle;"></a>
                        <a href="#"><img src="https://img.icons8.com/ios-filled/24/888888/youtube-play.png" alt="yt" style="width: 16px; height: 16px; vertical-align: middle;"></a>
                        <a href="#"><img src="https://img.icons8.com/ios-filled/24/888888/domain.png" alt="web" style="width: 16px; height: 16px; vertical-align: middle;"></a>
                    </div>
                    <div class="footer-tagline">Building a Safer Digital Tomorrow</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
