<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.5; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #ef4444; color: #ffffff; text-decoration: none; border-radius: 5px; margin-top: 15px; }
        .info-box { background: #fef2f2; border: 1px solid #fecaca; padding: 15px; border-radius: 5px; margin: 15px 0; color: #991b1b; }
    </style>
</head>
<body>
    <div class="container">
        <h2>{{ $title }}, {{ $tenant->business_name }}!</h2>
        
        <div class="info-box">
            <p>{{ $messageStr }}</p>
        </div>

        <p><strong>Application URL:</strong> <a href="{{ $domainUrl }}">{{ $domainUrl }}</a></p>

        <p>To avoid service interruptions and keep your application active, please log in to your client portal and renew your subscription as soon as possible.</p>

        <a href="https://tidcraft.com" class="btn">Login & Renew Now</a>

        <p style="margin-top: 30px; font-size: 12px; color: #666;">
            If you have already renewed, please ignore this email or contact support if your application is still blocked.
        </p>
    </div>
</body>
</html>
