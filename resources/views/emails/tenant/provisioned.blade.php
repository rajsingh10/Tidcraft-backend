<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.5; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #2563eb; color: #ffffff; text-decoration: none; border-radius: 5px; margin-top: 15px; }
        .info-box { background: #f3f4f6; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Your Application is Ready, {{ $tenant->business_name }}!</h2>
        
        <p>We are excited to let you know that your new application has been successfully provisioned and is now live.</p>
        
        <div class="info-box">
            <p><strong>Application URL:</strong> <a href="{{ $domainUrl }}">{{ $domainUrl }}</a></p>
        </div>

        <h3>Admin Login Credentials</h3>
        <p>You can use the following credentials to log in to your application's admin panel:</p>
        
        <div class="info-box">
            <p><strong>Email:</strong> {{ $adminEmail }}</p>
            <p><strong>Password:</strong> {{ $adminPassword }}</p>
        </div>

        <p><i>Note: We highly recommend changing your password after your first login for security purposes.</i></p>

        <a href="{{ $domainUrl }}" class="btn">Go to your Application</a>

        <p style="margin-top: 30px; font-size: 12px; color: #666;">
            If you have any questions or need support, please open a ticket from your client portal.
        </p>
    </div>
</body>
</html>
