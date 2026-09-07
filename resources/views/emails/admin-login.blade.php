<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login Detected</title>
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
            background-color: #0b57d0;
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
            color: #0b57d0;
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
        .location-card {
            margin-top: 25px;
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            text-align: left;
            position: relative;
            overflow: hidden;
            display: table;
            width: 100%;
            box-sizing: border-box;
        }
        .loc-icon {
            display: table-cell;
            width: 50px;
            vertical-align: top;
        }
        .loc-icon div {
            background-color: #e6f4ea;
            color: #137333;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            text-align: center;
            line-height: 40px;
        }
        .loc-icon div img {
            vertical-align: middle;
        }
        .loc-icon svg {
            width: 20px;
            height: 20px;
            fill: currentColor;
            vertical-align: middle;
        }
        .loc-details {
            display: table-cell;
            vertical-align: top;
        }
        .loc-title {
            color: #137333;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 4px;
        }
        .loc-address {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }
        .loc-coords {
            font-size: 13px;
            color: #666666;
            margin-bottom: 15px;
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
            background-color: #e6f4ea;
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
            color: #137333;
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
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="header-left">
                    <div class="brand-logo">
                        @php
                            $companyLogo = \App\Models\Setting::where('key', 'company_logo')->value('value');
                            $companyName = \App\Models\Setting::where('key', 'company_name')->value('value') ?? 'YourBrand';
                            $companyTagline = \App\Models\Setting::where('key', 'company_tagline')->value('value') ?? 'Manage • Monitor • Grow';
                            
                            $embedLogoPath = null;
                            if ($companyLogo) {
                                $logoPath = public_path(ltrim($companyLogo, '/'));
                                if (file_exists($logoPath)) {
                                    $embedLogoPath = $logoPath;
                                }
                            }
                            
                            // Simple OS/Browser parser from User Agent
                            $ua = $loginDetails['user_agent'] ?? '';
                            $os = 'Unknown OS';
                            if (preg_match('/windows nt 11/i', $ua)) $os = 'Windows 11';
                            elseif (preg_match('/windows nt 10/i', $ua)) $os = 'Windows 10';
                            elseif (preg_match('/windows nt/i', $ua)) $os = 'Windows';
                            elseif (preg_match('/mac os x/i', $ua)) $os = 'macOS';
                            elseif (preg_match('/linux/i', $ua)) $os = 'Linux';
                            elseif (preg_match('/iphone|ipad|ipod/i', $ua)) $os = 'iOS';
                            elseif (preg_match('/android/i', $ua)) $os = 'Android';
                            
                            $browser = 'Unknown Browser';
                            if (preg_match('/Edg/i', $ua)) $browser = 'Edge';
                            elseif (preg_match('/Chrome/i', $ua)) $browser = 'Chrome';
                            elseif (preg_match('/Safari/i', $ua)) $browser = 'Safari';
                            elseif (preg_match('/Firefox/i', $ua)) $browser = 'Firefox';
                        @endphp
                        @if($embedLogoPath && isset($message))
                            <img src="{{ $message->embed($embedLogoPath) }}" alt="Logo" style="width: 32px; height: 32px; border-radius: 4px; vertical-align: middle;">
                        @elseif($companyLogo)
                            <img src="{{ asset(ltrim($companyLogo, '/')) }}" alt="Logo" style="width: 32px; height: 32px; border-radius: 4px; vertical-align: middle;">
                        @else
                            <img src="https://img.icons8.com/ios-filled/50/ffffff/company.png" alt="Logo" style="width: 32px; height: 32px; vertical-align: middle;">
                        @endif
                        {{ $companyName }}
                    </div>
                    <div class="brand-tagline">{{ $companyTagline }}</div>
                </div>
                <div class="header-right">
                    Secure Today,<br>
                    A Safer Tomorrow.
                </div>
            </div>
        </div>

        <!-- Shield Icon -->
        <div class="shield-icon-container">
            <div class="shield-icon">
                <div>
                    <img src="https://img.icons8.com/ios-filled/50/ffffff/security-checked.png" alt="Shield" style="width: 24px; height: 24px;">
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="title">New <span>Admin Login</span> Detected</div>
            <div class="subtitle">A new login to your admin panel has been detected. Here are the details:</div>

            <table class="details-grid">
                <!-- Row 1 -->
                <tr>
                    <td>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <div><img src="https://img.icons8.com/ios-filled/50/0b57d0/calendar.png" alt="Date" style="width: 16px; height: 16px;"></div>
                            </div>
                            <div class="detail-text">
                                <div class="detail-label">Date & Time</div>
                                <div class="detail-value">{{ \Carbon\Carbon::parse($loginDetails['date_time'] ?? now())->format('d F Y, h:i A') }} (UTC)</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <div><img src="https://img.icons8.com/ios-filled/50/0b57d0/marker-a.png" alt="IP" style="width: 16px; height: 16px;"></div>
                            </div>
                            <div class="detail-text">
                                <div class="detail-label">IP Address</div>
                                <div class="detail-value">{{ $loginDetails['ip'] ?? 'Unknown' }}</div>
                            </div>
                        </div>
                    </td>
                </tr>
                <!-- Row 2 -->
                <tr>
                    <td>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <div><img src="https://img.icons8.com/ios-filled/50/0b57d0/mac-client.png" alt="Device" style="width: 16px; height: 16px;"></div>
                            </div>
                            <div class="detail-text">
                                <div class="detail-label">Device Name / Browser</div>
                                <div class="detail-value">{{ $os }} PC / {{ $browser }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <div><img src="https://img.icons8.com/ios-filled/50/0b57d0/fingerprint.png" alt="Device ID" style="width: 16px; height: 16px;"></div>
                            </div>
                            <div class="detail-text">
                                <div class="detail-label">Device ID</div>
                                <div class="detail-value">{{ $loginDetails['device_id'] ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </td>
                </tr>
                <!-- Row 3 -->
                <tr>
                    <td>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <div><img src="https://img.icons8.com/ios-filled/50/0b57d0/settings.png" alt="OS" style="width: 16px; height: 16px;"></div>
                            </div>
                            <div class="detail-text">
                                <div class="detail-label">Operating System</div>
                                <div class="detail-value">{{ $os }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <div><img src="https://img.icons8.com/ios-filled/50/0b57d0/map.png" alt="Location" style="width: 16px; height: 16px;"></div>
                            </div>
                            <div class="detail-text">
                                <div class="detail-label">Location</div>
                                <div class="detail-value">{{ $loginDetails['location_name'] ?? 'Unknown' }}</div>
                            </div>
                        </div>
                    </td>
                </tr>
                <!-- Row 4 -->
                <tr>
                    <td>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <div><img src="https://img.icons8.com/ios-filled/50/0b57d0/send.png" alt="Lat" style="width: 16px; height: 16px;"></div>
                            </div>
                            <div class="detail-text">
                                <div class="detail-label">Latitude</div>
                                <div class="detail-value">{{ $loginDetails['latitude'] ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <div><img src="https://img.icons8.com/ios-filled/50/0b57d0/send.png" alt="Long" style="width: 16px; height: 16px; transform: rotate(180deg);"></div>
                            </div>
                            <div class="detail-text">
                                <div class="detail-label">Longitude</div>
                                <div class="detail-value">{{ $loginDetails['longitude'] ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            @if(!empty($loginDetails['latitude']) && !empty($loginDetails['longitude']))
            <div class="location-card" style="background-image: url('https://img.freepik.com/free-vector/clean-light-blue-map-background-design_1017-26880.jpg'); background-size: cover; background-position: center;">
                <!-- Overlay to ensure text readability if map is too dark -->
                <div style="background-color: rgba(248, 249, 250, 0.85); position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 1;"></div>
                
                <div style="position: relative; z-index: 2; display: table; width: 100%;">
                    <div class="loc-icon">
                        <div><img src="https://img.icons8.com/ios-filled/50/137333/map-marker.png" alt="Map" style="width: 20px; height: 20px;"></div>
                    </div>
                    <div class="loc-details">
                        <div class="loc-title">Login Location</div>
                        <div class="loc-coords">Latitude: {{ $loginDetails['latitude'] }} | Longitude: {{ $loginDetails['longitude'] }}</div>
                        <a href="https://www.google.com/maps/search/?api=1&query={{ $loginDetails['latitude'] }},{{ $loginDetails['longitude'] }}" target="_blank" class="btn">View Location on Map &rarr;</a>
                    </div>
                    <!-- Right side map marker illustration like in the design -->
                    <div style="display: table-cell; vertical-align: middle; text-align: right; padding-right: 30px;">
                        <img src="https://img.icons8.com/color/96/000000/marker--v1.png" alt="Marker" style="width: 48px; height: 48px; filter: drop-shadow(0px 10px 10px rgba(0,0,0,0.2));">
                    </div>
                </div>
            </div>
            @endif

            <div class="alert-box">
                <div class="alert-icon">
                    <img src="https://img.icons8.com/ios-filled/50/137333/error.png" alt="Alert" style="width: 24px; height: 24px;">
                </div>
                <div class="alert-text">
                    If this login was not authorized, please secure your account immediately by changing your password and reviewing active sessions.
                </div>
            </div>

            <div class="automated-text">
                This is an automated email. Please do not reply to this email.
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
