<!DOCTYPE html>
<html>
<head>
    <title>New Support Ticket Created</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>New Support Ticket Created</h2>
    
    <p>A new support ticket has been created by <strong>{{ $user->name ?? 'Client' }}</strong> ({{ $user->email ?? 'N/A' }}).</p>
    
    <table style="border-collapse: collapse; width: 100%; max-width: 600px; margin-top: 20px;">
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold; width: 30%;">Client Name</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $user->name ?? 'Client' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Client Email</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $user->email ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Client Phone</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $user->phone_number ?? $user->contact ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Country Code</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $user->country_code ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">WhatsApp Number</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $user->whatsapp_number ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold; background-color: #f9f9f9;" colspan="2">Ticket Details</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold; width: 30%;">Ticket ID</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $ticket->ticket_id ?? $ticket->id }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Subject</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $ticket->subject }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Priority</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $ticket->priority }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Status</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $ticket->status }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Description</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{!! nl2br(e($ticket->description ?? 'N/A')) !!}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Date & Time</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ ($ticket->created_at ?? now())->format('Y-m-d H:i:s') }}</td>
        </tr>
    </table>
    
    <p style="margin-top: 30px;">
        <a href="{{ config('app.url') }}/admin/support-tickets/{{ $ticket->id }}" style="background-color: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;">View Ticket in Admin</a>
    </p>
    
    <p style="margin-top: 30px; font-size: 12px; color: #777;">
        This is an automated notification from your {{ $companyName }} platform.
    </p>
</body>
</html>
