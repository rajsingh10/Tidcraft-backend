<!DOCTYPE html>
<html>
<head>
    <title>Your Inquiry Has Been Received</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Hello {{ $inquiry->customer_name }},</p>

    <p>Thank you for contacting us.</p>

    <p>We have successfully received your inquiry. Our team will review your request and contact you as soon as possible.</p>

    <p>If you have any additional information or questions, please feel free to contact us.</p>

    <p>Thank you for your interest.</p>

    <p style="margin-top: 30px;">
        Best Regards,<br>
        <strong>{{ $companyName }}</strong><br>
        @if(!empty($companyPhone))
            Phone: {{ $companyPhone }}<br>
        @endif
        @if(!empty($companyEmail))
            Email: {{ $companyEmail }}<br>
        @endif
        @php
            $websiteUrl = !empty($companyWebsite) ? $companyWebsite : (\App\Models\Setting::where('key', 'company_website')->value('value') ?: \App\Helpers\UrlHelper::getFrontendUrl());
        @endphp
        <a href="{{ $websiteUrl }}">{{ $websiteUrl }}</a>
    </p>
</body>
</html>
