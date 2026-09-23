<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DNS Setup Instructions for Your Custom Domain - {{ $domain->domain }}</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f8fafc; font-family: 'Inter', Arial, sans-serif; color: #334155; }
        table { border-spacing: 0; border-collapse: collapse; }
        td { padding: 0; }
        
        .container { max-width: 650px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04); overflow: hidden; border: 1px solid #e2e8f0; }
        
        .header { padding: 28px 36px; background-color: #ffffff; border-bottom: 1px solid #f1f5f9; }
        .header-table { width: 100%; }
        .brand-logo img { height: 35px; }
        .header-nav { font-size: 13px; color: #64748b; text-align: right; }
        .header-nav span { margin: 0 5px; opacity: 0.5; }
        
        .content { padding: 36px; }
        
        .hero-title { font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1.25; margin: 0 0 10px 0; }
        .hero-title span { color: #2563eb; }
        .hero-desc { font-size: 14px; line-height: 1.6; color: #475569; margin: 0 0 24px 0; }
        
        .domain-badge-box { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #bfdbfe; border-radius: 10px; padding: 18px 24px; margin-bottom: 28px; }
        .domain-name { font-size: 20px; font-weight: 700; color: #1e3a8a; margin: 0 0 4px 0; }
        .domain-status { font-size: 13px; color: #2563eb; font-weight: 500; }
        
        .section-title { font-size: 16px; font-weight: 700; color: #0f172a; margin: 24px 0 12px 0; display: flex; align-items: center; }
        
        .dns-table { width: 100%; margin-bottom: 24px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; font-size: 13px; }
        .dns-table th { background-color: #f8fafc; color: #475569; font-weight: 600; padding: 12px 14px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .dns-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .dns-table tr:last-child td { border-bottom: none; }
        .record-tag { display: inline-block; padding: 4px 8px; border-radius: 4px; font-weight: 700; font-size: 12px; background-color: #dbeafe; color: #1d4ed8; }
        .code-val { font-family: 'Courier New', Courier, monospace; background-color: #f1f5f9; padding: 3px 8px; border-radius: 4px; font-weight: 600; color: #0f172a; font-size: 13px; }
        
        .info-card { background-color: #fefce8; border: 1px solid #fef08a; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px; }
        .info-card h4 { margin: 0 0 6px 0; color: #854d0e; font-size: 14px; font-weight: 700; }
        .info-card p { margin: 0; color: #713f12; font-size: 13px; line-height: 1.5; }
        
        .guide-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 24px; }
        .guide-title { font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 12px 0; }
        .guide-step { margin-bottom: 10px; font-size: 13px; line-height: 1.5; color: #475569; }
        .guide-step strong { color: #0f172a; }
        
        .cta-section { text-align: center; margin: 32px 0; }
        .cta-btn { display: inline-block; background-color: #2563eb; color: #ffffff !important; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-size: 14px; font-weight: 700; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
        
        .help-box { background-color: #eff6ff; border-radius: 8px; padding: 18px 22px; margin-bottom: 24px; }
        .help-table { width: 100%; }
        .help-text h4 { margin: 0 0 4px 0; color: #1e3a8a; font-size: 14px; }
        .help-text p { margin: 0; color: #1e40af; font-size: 13px; line-height: 1.5; }
        
        .footer { padding: 28px 36px; background-color: #ffffff; border-top: 1px solid #f1f5f9; font-size: 12px; color: #64748b; }
        .footer strong { color: #0f172a; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
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

        <div class="content">
            <p style="font-size: 15px; color: #475569; margin: 0 0 6px 0;">Hello {{ $tenant->client->name ?? $tenant->business_name ?? 'Client' }},</p>
            <h1 class="hero-title">Connect Your Custom Domain: <span>{{ $domain->domain }}</span></h1>
            <p class="hero-desc">
                To link your domain <strong>{{ $domain->domain }}</strong> to your {{ $tenant->product->name ?? 'application' }}, please update your DNS records at your domain registrar (such as GoDaddy, Namecheap, Cloudflare, or Google Domains).
            </p>

            <!-- Domain Overview -->
            <div class="domain-badge-box">
                <table width="100%">
                    <tr>
                        <td>
                            <div class="domain-name">{{ $domain->domain }}</div>
                            <div class="domain-status">&#9679; Pending DNS Verification</div>
                        </td>
                        <td align="right">
                            <span style="font-size: 12px; color: #64748b;">Target Server IP:</span><br>
                            <span class="code-val" style="font-size: 14px; color: #1e3a8a;">{{ $serverIp }}</span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Required DNS Records Table -->
            <div class="section-title">Required DNS Records</div>
            <table class="dns-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Host / Name</th>
                        <th>Points To / Value</th>
                        <th>TTL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dnsRecords as $record)
                    <tr>
                        <td><span class="record-tag">{{ $record['type'] }}</span></td>
                        <td><span class="code-val">{{ $record['name'] }}</span></td>
                        <td><span class="code-val">{{ $record['value'] }}</span></td>
                        <td>{{ $record['ttl'] }}s</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- DNS Propagation Alert -->
            <div class="info-card">
                <h4>DNS Propagation Time</h4>
                <p>
                    DNS changes usually take between 5 to 30 minutes to propagate worldwide, but in some rare cases may take up to 24-48 hours. Once configured, click the verification button below or verify directly from your dashboard.
                </p>
            </div>

            <!-- Registrar Quick Instructions -->
            <div class="guide-box">
                <div class="guide-title">Quick Registrar Instructions:</div>
                <div class="guide-step">
                    <strong>1. GoDaddy:</strong> Go to <i>Domain Portfolio</i> &rarr; Select domain &rarr; <i>DNS</i> &rarr; Click <i>Add New Record</i> &rarr; Choose <strong>A</strong>, enter <code>@</code> in Name, enter <code>{{ $serverIp }}</code> in Value.
                </div>
                <div class="guide-step">
                    <strong>2. Namecheap:</strong> Go to <i>Domain List</i> &rarr; Click <i>Manage</i> &rarr; <i>Advanced DNS</i> &rarr; Click <i>Add New Record</i> &rarr; Choose <strong>A Record</strong>, enter <code>@</code> in Host, enter <code>{{ $serverIp }}</code> in IP.
                </div>
                <div class="guide-step">
                    <strong>3. Cloudflare:</strong> Go to your domain &rarr; <i>DNS Records</i> &rarr; Click <i>Add Record</i> &rarr; Type <strong>A</strong>, Name <code>@</code>, IPv4 Address <code>{{ $serverIp }}</code>. (Note: Turn proxy status to <i>DNS Only</i> for initial verification).
                </div>
            </div>

            <!-- CTA -->
            <div class="cta-section">
                <a href="{{ config('app.url') }}/client/purchases/{{ $tenant->uuid }}" class="cta-btn">
                    Check DNS Status &rarr;
                </a>
            </div>

            <!-- Help Box -->
            <div class="help-box">
                <table class="help-table">
                    <tr>
                        <td class="help-text">
                            <h4>Need Assistance with DNS Setup?</h4>
                            <p>If you're unsure how to add these records, reply to this email or reach out to our team at {{ $settings['company_email'] ?? 'support@tidcraft.com' }} and we'll guide you step by step.</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <table width="100%">
                <tr>
                    <td>
                        <strong>{{ $settings['company_name'] ?? 'Tidcraft' }}</strong><br>
                        {{ $settings['company_address'] ?? 'Tech for a Brighter Tomorrow' }}
                    </td>
                    <td align="right" style="vertical-align: top;">
                        Questions? <a href="mailto:{{ $settings['company_email'] ?? 'support@tidcraft.com' }}" style="color: #2563eb; text-decoration: none;">Contact Support</a>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
