<!DOCTYPE html>
<html>
<head>
    <title>New Inquiry Received</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>New Inquiry Received</h2>
    
    <p>A new inquiry has been submitted by <strong>{{ $inquiry->customer_name }}</strong>.</p>
    
    <table style="border-collapse: collapse; width: 100%; max-width: 600px; margin-top: 20px;">
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold; width: 30%;">Client/User Name</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $inquiry->customer_name }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Email</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $inquiry->email }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Phone</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $inquiry->phone ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Inquiry Subject/Service</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $inquiry->project_id ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Inquiry Message</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{!! nl2br(e($inquiry->description ?? 'N/A')) !!}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Date & Time</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $inquiry->created_at ? $inquiry->created_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Inquiry ID</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $inquiry->id }}</td>
        </tr>
    </table>
    
    <p style="margin-top: 30px;">
        <a href="{{ config('app.url') }}/api/inquiries/{{ $inquiry->id }}" style="background-color: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;">View Inquiry via API</a>
    </p>
    
    <p style="margin-top: 30px; font-size: 12px; color: #777;">
        This is an automated notification from your {{ $companyName }} platform.
    </p>
</body>
</html>
