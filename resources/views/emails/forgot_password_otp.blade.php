<!DOCTYPE html>
<html>
<head>
    <title>Password Reset OTP</title>
</head>
<body>
    <p>Hello,</p>
    <p>You have requested to reset your password. Use the following One-Time Password (OTP) to complete the process:</p>
    <h2 style="background: #f4f4f4; padding: 10px; display: inline-block; letter-spacing: 2px;">{{ $otp }}</h2>
    <p>This code will expire in 15 minutes.</p>
    <p>If you did not request a password reset, please ignore this email.</p>
    <p>Thanks,<br>{{ config('app.name') }}</p>
</body>
</html>
