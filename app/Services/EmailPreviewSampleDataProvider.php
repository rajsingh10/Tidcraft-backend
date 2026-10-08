<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Payment;
use App\Models\Inquiry;
use App\Models\Domain;
use App\Models\Setting;
use Carbon\Carbon;

class EmailPreviewSampleDataProvider
{
    /**
     * Get or create a sample user object.
     */
    public static function getSampleUser(array $overrides = [])
    {
        $existing = User::first();
        if ($existing) {
            $user = clone $existing;
        } else {
            $user = new User();
            $user->id = 1;
            $user->name = 'Alex Morgan';
            $user->email = 'alex.morgan@example.com';
            $user->phone = '+1 (555) 234-5678';
            $user->created_at = Carbon::now()->subDays(5);
        }

        foreach ($overrides as $k => $v) {
            $user->{$k} = $v;
        }

        return $user;
    }

    /**
     * Get or create a sample tenant object.
     */
    public static function getSampleTenant(array $overrides = [])
    {
        $existing = Tenant::with(['client', 'domains', 'payments'])->first();
        if ($existing) {
            $tenant = clone $existing;
        } else {
            $tenant = new Tenant();
            $tenant->id = 1;
            $tenant->uuid = 'tnt_' . substr(md5(uniqid()), 0, 12);
            $tenant->business_name = 'Acme Global Ventures';
            $tenant->subdomain = 'acme-store';
            $tenant->custom_domain = 'acmeglobal.com';
            $tenant->status = 'active';
            $tenant->subscription_end_date = Carbon::now()->addDays(30)->format('Y-m-d H:i:s');
            $tenant->created_at = Carbon::now()->subDays(10);
        }

        if (empty($tenant->client)) {
            $tenant->client = self::getSampleUser();
        }

        foreach ($overrides as $k => $v) {
            $tenant->{$k} = $v;
        }

        return $tenant;
    }

    /**
     * Get or create a sample payment object.
     */
    public static function getSamplePayment(array $overrides = [])
    {
        $existing = Payment::first();
        if ($existing) {
            $payment = clone $existing;
        } else {
            $payment = new Payment();
            $payment->id = 101;
            $payment->order_id = 'ORD-' . strtoupper(substr(uniqid(), 0, 8));
            $payment->transaction_id = 'pay_' . substr(md5(uniqid()), 0, 14);
            $payment->amount = 499.00;
            $payment->currency = 'USD';
            $payment->payment_method = 'Credit Card (Stripe)';
            $payment->status = 'success';
            $payment->created_at = Carbon::now()->subMinutes(15);
        }

        foreach ($overrides as $k => $v) {
            $payment->{$k} = $v;
        }

        return $payment;
    }

    /**
     * Get or create a sample inquiry object.
     */
    public static function getSampleInquiry(array $overrides = [])
    {
        $existing = Inquiry::first();
        if ($existing) {
            $inquiry = clone $existing;
        } else {
            $inquiry = new Inquiry();
            $inquiry->id = 55;
            $inquiry->customer_name = 'Sarah Connor';
            $inquiry->name = 'Sarah Connor';
            $inquiry->email = 'sarah.connor@cyberdyne.io';
            $inquiry->phone = '+1 (555) 987-6543';
            $inquiry->project_id = 'Enterprise SaaS Inquiries';
            $inquiry->subject = 'Pricing inquiry for multi-tenant white label deployment';
            $inquiry->description = "Hello,\n\nWe are looking to roll out our white-label portal to 500+ clients across North America. Could you provide details on custom DNS provisioning, SLA terms, and automated backups?\n\nBest regards,\nSarah Connor";
            $inquiry->message = $inquiry->description;
            $inquiry->created_at = Carbon::now()->subHours(2);
        }

        foreach ($overrides as $k => $v) {
            $inquiry->{$k} = $v;
        }

        return $inquiry;
    }

    /**
     * Get or create a sample domain object.
     */
    public static function getSampleDomain(array $overrides = [])
    {
        $existing = Domain::first();
        if ($existing) {
            $domain = clone $existing;
        } else {
            $domain = new Domain();
            $domain->id = 1;
            $domain->domain = 'portal.acmeglobal.com';
            $domain->is_custom = true;
            $domain->status = 'pending_dns';
            $domain->created_at = Carbon::now()->subDays(1);
        }

        foreach ($overrides as $k => $v) {
            $domain->{$k} = $v;
        }

        return $domain;
    }

    /**
     * Get current application settings.
     */
    public static function getSampleSettings()
    {
        $keys = [
            'company_name',
            'company_favicon',
            'company_short_logo',
            'company_logo',
            'company_tagline',
            'company_email',
            'company_phone',
            'company_address',
        ];

        $settings = Setting::whereIn('key', $keys)->pluck('value', 'key')->toArray();

        return array_merge([
            'company_name' => config('app.name', 'TidCraft'),
            'company_tagline' => 'Next-Gen Multi-Tenant Platform',
            'company_email' => config('mail.from.address', 'support@tidcraft.com'),
            'company_phone' => '+1 (800) 555-0199',
            'company_address' => '100 Innovation Way, Suite 400, San Francisco, CA',
            'company_logo' => null,
            'company_short_logo' => null,
            'company_favicon' => null,
        ], $settings);
    }

    /**
     * Get default sample replacements and variable metadata for a dynamic DB template by slug.
     */
    public static function getDynamicTemplateData(string $slug): array
    {
        $appUrl = config('app.url', 'http://127.0.0.1:8000');
        $dnsTable = '<table style="width:100%; border-collapse:collapse; margin:15px 0; font-family:monospace; font-size:13px;">
            <thead>
                <tr style="background:#f1f5f9; text-align:left;">
                    <th style="padding:10px; border:1px solid #cbd5e1;">Type</th>
                    <th style="padding:10px; border:1px solid #cbd5e1;">Host / Name</th>
                    <th style="padding:10px; border:1px solid #cbd5e1;">Value / Points To</th>
                    <th style="padding:10px; border:1px solid #cbd5e1;">TTL</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding:10px; border:1px solid #cbd5e1; font-weight:bold; color:#2563eb;">A</td>
                    <td style="padding:10px; border:1px solid #cbd5e1;">@</td>
                    <td style="padding:10px; border:1px solid #cbd5e1;">159.89.172.95</td>
                    <td style="padding:10px; border:1px solid #cbd5e1;">Automatic (300)</td>
                </tr>
                <tr>
                    <td style="padding:10px; border:1px solid #cbd5e1; font-weight:bold; color:#2563eb;">CNAME</td>
                    <td style="padding:10px; border:1px solid #cbd5e1;">www</td>
                    <td style="padding:10px; border:1px solid #cbd5e1;">acmeglobal.com</td>
                    <td style="padding:10px; border:1px solid #cbd5e1;">Automatic (300)</td>
                </tr>
            </tbody>
        </table>';

        $defaults = [
            'register' => [
                'replacements' => [
                    '{name}' => 'Alex Morgan',
                    '{email}' => 'alex.morgan@example.com',
                    '{login_url}' => $appUrl . '/login',
                    '{contact_url}' => $appUrl . '/contact-us',
                    '{created_at}' => Carbon::now()->format('Y-m-d H:i:s'),
                    '{year}' => date('Y'),
                ],
                'variables' => [
                    '{name}' => ['type' => 'string', 'desc' => 'Registered user full name'],
                    '{email}' => ['type' => 'string', 'desc' => 'User email address'],
                    '{login_url}' => ['type' => 'url', 'desc' => 'Direct link to client login portal (/login)'],
                    '{contact_url}' => ['type' => 'url', 'desc' => 'Direct link to contact support page (/contact-us)'],
                    '{created_at}' => ['type' => 'datetime', 'desc' => 'Account creation date and time'],
                    '{year}' => ['type' => 'string', 'desc' => 'Current year for footer copyright'],
                ],
                'category' => 'Client Auth'
            ],
            'Forgot_Password' => [
                'replacements' => [
                    '{name}' => 'Alex Morgan',
                    '{otp}' => '849201',
                    '{reset_url}' => $appUrl . '/client/reset-password?email=alex.morgan@example.com&token=sample_token',
                    '{email}' => 'alex.morgan@example.com',
                    '{expires_in}' => '10 minutes',
                    '{year}' => date('Y'),
                ],
                'variables' => [
                    '{name}' => ['type' => 'string', 'desc' => 'User full name'],
                    '{otp}' => ['type' => 'string', 'desc' => '6-digit OTP verification code'],
                    '{reset_url}' => ['type' => 'url', 'desc' => 'Password reset action link'],
                    '{email}' => ['type' => 'string', 'desc' => 'Account email address'],
                    '{expires_in}' => ['type' => 'string', 'desc' => 'OTP expiration window (e.g. 10 minutes)'],
                    '{year}' => ['type' => 'string', 'desc' => 'Current year for footer copyright'],
                ],
                'category' => 'Client Auth'
            ],
            'Setup_email' => [
                'replacements' => [
                    '{name}' => 'Alex Morgan',
                    '{business_name}' => 'Acme Global Ventures',
                    '{domain_url}' => 'https://portal.acmeglobal.com',
                    '{admin_url}' => 'https://portal.acmeglobal.com/admin',
                    '{login_url}' => 'https://portal.acmeglobal.com/login',
                    '{admin_email}' => 'admin@acmeglobal.com',
                    '{admin_password}' => 'SecretP@ss123!',
                    '{year}' => date('Y'),
                ],
                'variables' => [
                    '{name}' => ['type' => 'string', 'desc' => 'Client / business owner name'],
                    '{business_name}' => ['type' => 'string', 'desc' => 'Tenant business name'],
                    '{domain_url}' => ['type' => 'url', 'desc' => 'Public application website URL'],
                    '{admin_url}' => ['type' => 'url', 'desc' => 'Application administrator back-office URL'],
                    '{login_url}' => ['type' => 'url', 'desc' => 'Direct sign-in portal URL'],
                    '{admin_email}' => ['type' => 'string', 'desc' => 'Provisioned tenant admin email address'],
                    '{admin_password}' => ['type' => 'string', 'desc' => 'Initial auto-generated admin password'],
                    '{year}' => ['type' => 'string', 'desc' => 'Current year for copyright'],
                ],
                'category' => 'Tenant Provisioning'
            ],
            'Admin_Inquiry' => [
                'replacements' => [
                    '{name}' => 'Sarah Connor',
                    '{email}' => 'sarah.connor@cyberdyne.io',
                    '{phone}' => '+1 (555) 987-6543',
                    '{subject}' => 'Enterprise SaaS Inquiries',
                    '{message}' => 'We would like to connect with your enterprise onboarding team regarding 500+ client accounts.',
                    '{date}' => Carbon::now()->format('Y-m-d H:i:s'),
                    '{year}' => date('Y'),
                ],
                'variables' => [
                    '{name}' => ['type' => 'string', 'desc' => 'Prospect / inquirer full name'],
                    '{email}' => ['type' => 'string', 'desc' => 'Inquirer contact email'],
                    '{phone}' => ['type' => 'string', 'desc' => 'Contact phone number'],
                    '{subject}' => ['type' => 'string', 'desc' => 'Topic or product category of interest'],
                    '{message}' => ['type' => 'string', 'desc' => 'Full inquiry description / comments'],
                    '{date}' => ['type' => 'datetime', 'desc' => 'Submission timestamp'],
                    '{year}' => ['type' => 'string', 'desc' => 'Current year'],
                ],
                'category' => 'Admin Notifications'
            ],
            'Admin_Login' => [
                'replacements' => [
                    '{name}' => 'Administrator',
                    '{ip_address}' => '192.168.1.105',
                    '{user_agent}' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36',
                    '{time}' => Carbon::now()->format('Y-m-d H:i:s'),
                    '{year}' => date('Y'),
                ],
                'variables' => [
                    '{name}' => ['type' => 'string', 'desc' => 'Admin username or display name'],
                    '{ip_address}' => ['type' => 'string', 'desc' => 'IP address of successful sign in'],
                    '{user_agent}' => ['type' => 'string', 'desc' => 'Browser / device user agent string'],
                    '{time}' => ['type' => 'datetime', 'desc' => 'Time of login event'],
                    '{year}' => ['type' => 'string', 'desc' => 'Current year'],
                ],
                'category' => 'Admin Notifications'
            ],
            'Payment_Failed' => [
                'replacements' => [
                    '{name}' => 'Alex Morgan',
                    '{business_name}' => 'Acme Global Ventures',
                    '{amount}' => '499.00 USD',
                    '{order_id}' => 'ORD-982143',
                    '{reason}' => 'Card issuer declined transaction (insufficient funds / security hold)',
                    '{retry_url}' => $appUrl . '/client/purchases/retry?order_id=ORD-982143',
                    '{year}' => date('Y'),
                ],
                'variables' => [
                    '{name}' => ['type' => 'string', 'desc' => 'Client / customer name'],
                    '{business_name}' => ['type' => 'string', 'desc' => 'Tenant or company name'],
                    '{amount}' => ['type' => 'string', 'desc' => 'Charge amount and currency'],
                    '{order_id}' => ['type' => 'string', 'desc' => 'Internal order / checkout identifier'],
                    '{reason}' => ['type' => 'string', 'desc' => 'Failure diagnostic or gateway message'],
                    '{retry_url}' => ['type' => 'url', 'desc' => 'Direct link to retry payment with another card'],
                    '{year}' => ['type' => 'string', 'desc' => 'Current year'],
                ],
                'category' => 'Billing & Payments'
            ],
            'Payment_Received' => [
                'replacements' => [
                    '{name}' => 'Alex Morgan',
                    '{business_name}' => 'Acme Global Ventures',
                    '{amount}' => '499.00',
                    '{currency}' => 'USD',
                    '{order_id}' => 'ORD-982143',
                    '{payment_id}' => 'pay_871236812736',
                    '{payment_method}' => 'Credit Card (Stripe)',
                    '{plan_name}' => 'Pro Multi-Tenant SaaS Plan',
                    '{date}' => Carbon::now()->format('Y-m-d H:i:s'),
                    '{year}' => date('Y'),
                ],
                'variables' => [
                    '{name}' => ['type' => 'string', 'desc' => 'Customer name'],
                    '{business_name}' => ['type' => 'string', 'desc' => 'Tenant business name'],
                    '{amount}' => ['type' => 'string', 'desc' => 'Payment amount'],
                    '{currency}' => ['type' => 'string', 'desc' => 'Currency code (USD, INR, EUR)'],
                    '{order_id}' => ['type' => 'string', 'desc' => 'Order reference ID'],
                    '{payment_id}' => ['type' => 'string', 'desc' => 'Gateway transaction reference ID'],
                    '{payment_method}' => ['type' => 'string', 'desc' => 'Payment processor or card brand'],
                    '{plan_name}' => ['type' => 'string', 'desc' => 'Purchased subscription package / add-on'],
                    '{date}' => ['type' => 'datetime', 'desc' => 'Payment settlement timestamp'],
                    '{year}' => ['type' => 'string', 'desc' => 'Current year'],
                ],
                'category' => 'Billing & Payments'
            ],
            'Your_Application_is_Ready' => [
                'replacements' => [
                    '{name}' => 'Alex Morgan',
                    '{business_name}' => 'Acme Global Ventures',
                    '{domain_url}' => 'https://portal.acmeglobal.com',
                    '{admin_url}' => 'https://portal.acmeglobal.com/admin',
                    '{login_url}' => 'https://portal.acmeglobal.com/login',
                    '{admin_email}' => 'admin@acmeglobal.com',
                    '{admin_password}' => 'SecretP@ss123!',
                    '{subdomain}' => 'acme-store.tidcraft.com',
                    '{custom_domain}' => 'portal.acmeglobal.com',
                    '{domain}' => 'portal.acmeglobal.com',
                    '{year}' => date('Y'),
                ],
                'variables' => [
                    '{name}' => ['type' => 'string', 'desc' => 'Client full name'],
                    '{business_name}' => ['type' => 'string', 'desc' => 'Tenant / Business display name'],
                    '{domain_url}' => ['type' => 'url', 'desc' => 'Public live application domain'],
                    '{admin_url}' => ['type' => 'url', 'desc' => 'Tenant admin console URL'],
                    '{login_url}' => ['type' => 'url', 'desc' => 'Tenant portal sign in link'],
                    '{admin_email}' => ['type' => 'string', 'desc' => 'Tenant root administrator email'],
                    '{admin_password}' => ['type' => 'string', 'desc' => 'Auto-generated temporary password'],
                    '{subdomain}' => ['type' => 'string', 'desc' => 'Platform subdomain fallback'],
                    '{custom_domain}' => ['type' => 'string', 'desc' => 'Linked custom domain (if configured)'],
                    '{domain}' => ['type' => 'string', 'desc' => 'Primary active domain host'],
                    '{year}' => ['type' => 'string', 'desc' => 'Current year'],
                ],
                'category' => 'Tenant Provisioning'
            ],
            'Your_Inquiry_Has_Been_Received' => [
                'replacements' => [
                    '{name}' => 'Sarah Connor',
                    '{subject}' => 'Enterprise SaaS Inquiries',
                    '{message}' => 'We would like to connect with your enterprise onboarding team regarding 500+ client accounts.',
                    '{year}' => date('Y'),
                ],
                'variables' => [
                    '{name}' => ['type' => 'string', 'desc' => 'Customer name'],
                    '{subject}' => ['type' => 'string', 'desc' => 'Inquiry subject line'],
                    '{message}' => ['type' => 'string', 'desc' => 'Customer inquiry message content'],
                    '{year}' => ['type' => 'string', 'desc' => 'Current year'],
                ],
                'category' => 'Client Inquiries'
            ],
            'Subscription_Expiry' => [
                'replacements' => [
                    '{name}' => 'Alex Morgan',
                    '{tenant_name}' => 'Acme Global Ventures',
                    '{business_name}' => 'Acme Global Ventures',
                    '{plan_name}' => 'Enterprise Pro Cloud',
                    '{expiry_date}' => Carbon::now()->addDays(3)->format('F d, Y'),
                    '{renew_url}' => $appUrl . '/client/purchases/renew',
                    '{year}' => date('Y'),
                ],
                'variables' => [
                    '{name}' => ['type' => 'string', 'desc' => 'Client / contact name'],
                    '{tenant_name}' => ['type' => 'string', 'desc' => 'Business tenant name'],
                    '{business_name}' => ['type' => 'string', 'desc' => 'Business tenant name'],
                    '{plan_name}' => ['type' => 'string', 'desc' => 'Expiring plan title'],
                    '{expiry_date}' => ['type' => 'date', 'desc' => 'Exact date subscription ends'],
                    '{renew_url}' => ['type' => 'url', 'desc' => 'Quick renewal link'],
                    '{year}' => ['type' => 'string', 'desc' => 'Current year'],
                ],
                'category' => 'Billing & Payments'
            ],
            'Your_Account_Has_Been_Suspended' => [
                'replacements' => [
                    '{name}' => 'Alex Morgan',
                    '{business_name}' => 'Acme Global Ventures',
                    '{reason}' => 'Overdue invoice payment / subscription expiration grace period exceeded.',
                    '{contact_url}' => $appUrl . '/client/support',
                    '{year}' => date('Y'),
                ],
                'variables' => [
                    '{name}' => ['type' => 'string', 'desc' => 'Account owner name'],
                    '{business_name}' => ['type' => 'string', 'desc' => 'Suspended tenant business name'],
                    '{reason}' => ['type' => 'string', 'desc' => 'Account suspension reason'],
                    '{contact_url}' => ['type' => 'url', 'desc' => 'Help desk / support escalation link'],
                    '{year}' => ['type' => 'string', 'desc' => 'Current year'],
                ],
                'category' => 'Tenant Management'
            ],
            'foodapp-whitelabel-setup' => [
                'replacements' => [
                    '{name}' => 'Alex Morgan',
                    '{app_name}' => 'FoodApp White Label Delivery',
                    '{submission_link}' => $appUrl . '/client/branding/foodapp',
                    '{company_name}' => 'TidCraft',
                    '{year}' => date('Y'),
                ],
                'variables' => [
                    '{name}' => ['type' => 'string', 'desc' => 'Client name'],
                    '{app_name}' => ['type' => 'string', 'desc' => 'Application product name'],
                    '{submission_link}' => ['type' => 'url', 'desc' => 'Branding assets upload portal link'],
                    '{company_name}' => ['type' => 'string', 'desc' => 'Platform name'],
                    '{year}' => ['type' => 'string', 'desc' => 'Current year'],
                ],
                'category' => 'White Label Setup'
            ],
            'parkmeapp-whitelabel-setup' => [
                'replacements' => [
                    '{name}' => 'Alex Morgan',
                    '{app_name}' => 'ParkMe Smart Parking Suite',
                    '{submission_link}' => $appUrl . '/client/branding/parkmeapp',
                    '{company_name}' => 'TidCraft',
                    '{year}' => date('Y'),
                ],
                'variables' => [
                    '{name}' => ['type' => 'string', 'desc' => 'Client name'],
                    '{app_name}' => ['type' => 'string', 'desc' => 'Application product name'],
                    '{submission_link}' => ['type' => 'url', 'desc' => 'Branding assets upload portal link'],
                    '{company_name}' => ['type' => 'string', 'desc' => 'Platform name'],
                    '{year}' => ['type' => 'string', 'desc' => 'Current year'],
                ],
                'category' => 'White Label Setup'
            ],
            'Custom_Domain_Dns_Setup' => [
                'replacements' => [
                    '{name}' => 'Alex Morgan',
                    '{domain}' => 'portal.acmeglobal.com',
                    '{server_ip}' => '159.89.172.95',
                    '{dns_records_table}' => $dnsTable,
                    '{verify_url}' => $appUrl . '/client/purchases/dns/verify?domain=portal.acmeglobal.com',
                    '{product_name}' => 'Nexira Cloud Suite',
                    '{year}' => date('Y'),
                ],
                'variables' => [
                    '{name}' => ['type' => 'string', 'desc' => 'Client contact name'],
                    '{domain}' => ['type' => 'string', 'desc' => 'Custom domain to connect (e.g. portal.acme.com)'],
                    '{server_ip}' => ['type' => 'string', 'desc' => 'Server public IP address for A Record'],
                    '{dns_records_table}' => ['type' => 'html', 'desc' => 'Pre-formatted HTML table with DNS records to add'],
                    '{verify_url}' => ['type' => 'url', 'desc' => 'One-click DNS propagation verification link'],
                    '{product_name}' => ['type' => 'string', 'desc' => 'Product / SaaS software brand name'],
                    '{year}' => ['type' => 'string', 'desc' => 'Current year'],
                ],
                'category' => 'Domain & DNS'
            ],
            'Newsletter_Subscription' => [
                'replacements' => [
                    '{email}' => 'subscriber@example.com',
                    '{unsubscribe_url}' => $appUrl . '/newsletter/unsubscribe?token=sample_token',
                    '{year}' => date('Y'),
                ],
                'variables' => [
                    '{email}' => ['type' => 'string', 'desc' => 'Subscriber email address'],
                    '{unsubscribe_url}' => ['type' => 'url', 'desc' => 'Direct one-click unsubscribe link'],
                    '{year}' => ['type' => 'string', 'desc' => 'Current year'],
                ],
                'category' => 'Marketing'
            ],
        ];

        return $defaults[$slug] ?? [
            'replacements' => [
                '{name}' => 'Valued Client',
                '{email}' => 'client@example.com',
                '{year}' => date('Y'),
            ],
            'variables' => [
                '{name}' => ['type' => 'string', 'desc' => 'Recipient name'],
                '{email}' => ['type' => 'string', 'desc' => 'Recipient email'],
                '{year}' => ['type' => 'string', 'desc' => 'Current year'],
            ],
            'category' => 'General'
        ];
    }

    /**
     * Get metadata and a factory callback for all Blade Mailable classes.
     */
    public static function getMailableRegistry(): array
    {
        $appUrl = config('app.url', 'http://127.0.0.1:8000');

        return [
            'ClientRegisteredMail' => [
                'title' => 'Client Welcome / Registration (Blade)',
                'class' => \App\Mail\ClientRegisteredMail::class,
                'view' => 'emails.client_registered',
                'category' => 'Client Auth',
                'description' => 'Sent to clients when they sign up on the platform.',
                'variables' => [
                    '$user->name' => ['type' => 'string', 'desc' => 'Registered user full name', 'example' => 'Alex Morgan'],
                    '$user->email' => ['type' => 'string', 'desc' => 'Registered account email', 'example' => 'alex.morgan@example.com'],
                    '$settings[\'company_name\']' => ['type' => 'string', 'desc' => 'Platform company name', 'example' => 'TidCraft'],
                    '$settings[\'company_logo\']' => ['type' => 'string', 'desc' => 'Platform company logo URL', 'example' => '/storage/logo.png'],
                ],
                'sample_data' => [
                    'user_name' => 'Alex Morgan',
                    'user_email' => 'alex.morgan@example.com',
                ],
                'factory' => function (array $data = []) {
                    $user = self::getSampleUser([
                        'name' => $data['user_name'] ?? 'Alex Morgan',
                        'email' => $data['user_email'] ?? 'alex.morgan@example.com',
                    ]);
                    return new \App\Mail\ClientRegisteredMail($user);
                }
            ],
            'ForgotPasswordOtpMail' => [
                'title' => 'Forgot Password OTP (Blade)',
                'class' => \App\Mail\ForgotPasswordOtpMail::class,
                'view' => 'emails.forgot_password_otp',
                'category' => 'Client Auth',
                'description' => 'Sends a 6-digit OTP to the user for resetting their forgotten password.',
                'variables' => [
                    '$otp' => ['type' => 'string', 'desc' => '6-digit OTP security code', 'example' => '849201'],
                ],
                'sample_data' => [
                    'otp' => '849201',
                ],
                'factory' => function (array $data = []) {
                    $otp = $data['otp'] ?? '849201';
                    return new \App\Mail\ForgotPasswordOtpMail($otp);
                }
            ],
            'AdminNewClientMail' => [
                'title' => 'Admin Alert: New Client Registered (Blade)',
                'class' => \App\Mail\AdminNewClientMail::class,
                'view' => 'emails.admin_new_client',
                'category' => 'Admin Notifications',
                'description' => 'Alerts admins whenever a new customer registers on the site.',
                'variables' => [
                    '$user->name' => ['type' => 'string', 'desc' => 'Newly registered user full name', 'example' => 'Alex Morgan'],
                    '$user->email' => ['type' => 'string', 'desc' => 'User email address', 'example' => 'alex.morgan@example.com'],
                    '$user->created_at' => ['type' => 'datetime', 'desc' => 'Registration timestamp', 'example' => Carbon::now()->toDateTimeString()],
                ],
                'sample_data' => [
                    'user_name' => 'Alex Morgan',
                    'user_email' => 'alex.morgan@example.com',
                ],
                'factory' => function (array $data = []) {
                    $user = self::getSampleUser([
                        'name' => $data['user_name'] ?? 'Alex Morgan',
                        'email' => $data['user_email'] ?? 'alex.morgan@example.com',
                    ]);
                    return new \App\Mail\AdminNewClientMail($user);
                }
            ],
            'AdminLoginNotification' => [
                'title' => 'Admin Login Detected (Blade)',
                'class' => \App\Mail\AdminLoginNotification::class,
                'view' => 'emails.admin-login',
                'category' => 'Admin Notifications',
                'description' => 'Security alert sent to admins upon successful sign in to the admin panel.',
                'variables' => [
                    '$loginDetails[\'ip\']' => ['type' => 'string', 'desc' => 'Client IP address', 'example' => '192.168.1.105'],
                    '$loginDetails[\'date_time\']' => ['type' => 'datetime', 'desc' => 'Sign in timestamp', 'example' => Carbon::now()->toDateTimeString()],
                    '$loginDetails[\'user_agent\']' => ['type' => 'string', 'desc' => 'Browser and OS signature', 'example' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0'],
                    '$loginDetails[\'device_id\']' => ['type' => 'string', 'desc' => 'Unique device identifier', 'example' => 'DEV-98124'],
                ],
                'sample_data' => [
                    'ip' => '192.168.1.105',
                    'date_time' => Carbon::now()->toDateTimeString(),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
                    'device_id' => 'DEV-98124',
                ],
                'factory' => function (array $data = []) {
                    $loginDetails = [
                        'ip' => $data['ip'] ?? '192.168.1.105',
                        'date_time' => $data['date_time'] ?? Carbon::now()->toDateTimeString(),
                        'user_agent' => $data['user_agent'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
                        'device_id' => $data['device_id'] ?? 'DEV-98124',
                    ];
                    return new \App\Mail\AdminLoginNotification($loginDetails);
                }
            ],
            'AdminInquiryNotification' => [
                'title' => 'Admin Alert: New Lead / Inquiry (Blade)',
                'class' => \App\Mail\AdminInquiryNotification::class,
                'view' => 'emails.admin-inquiry',
                'category' => 'Admin Notifications',
                'description' => 'Sent to administrator inbox when a contact or sales lead is received.',
                'variables' => [
                    '$inquiry->customer_name' => ['type' => 'string', 'desc' => 'Prospect name', 'example' => 'Sarah Connor'],
                    '$inquiry->email' => ['type' => 'string', 'desc' => 'Prospect email', 'example' => 'sarah.connor@cyberdyne.io'],
                    '$inquiry->phone' => ['type' => 'string', 'desc' => 'Prospect phone number', 'example' => '+1 (555) 987-6543'],
                    '$inquiry->project_id' => ['type' => 'string', 'desc' => 'Requested service / product category', 'example' => 'Enterprise SaaS'],
                    '$inquiry->description' => ['type' => 'string', 'desc' => 'Full inquiry message', 'example' => 'Looking for enterprise deployment details.'],
                ],
                'sample_data' => [
                    'customer_name' => 'Sarah Connor',
                    'email' => 'sarah.connor@cyberdyne.io',
                    'phone' => '+1 (555) 987-6543',
                    'project_id' => 'Enterprise SaaS',
                    'description' => 'We are looking to roll out custom white-label solutions to 500+ clients across North America. Please provide pricing and SLA.',
                ],
                'factory' => function (array $data = []) {
                    $inquiry = self::getSampleInquiry($data);
                    return new \App\Mail\AdminInquiryNotification($inquiry);
                }
            ],
            'ClientInquiryConfirmation' => [
                'title' => 'Client Inquiry Auto-Confirmation (Blade)',
                'class' => \App\Mail\ClientInquiryConfirmation::class,
                'view' => 'emails.client-inquiry',
                'category' => 'Client Inquiries',
                'description' => 'Automated acknowledgment sent to the person who submitted a contact form.',
                'variables' => [
                    '$inquiry->customer_name' => ['type' => 'string', 'desc' => 'Inquirer full name', 'example' => 'Sarah Connor'],
                    '$inquiry->subject' => ['type' => 'string', 'desc' => 'Inquiry subject line', 'example' => 'Enterprise Inquiries'],
                    '$inquiry->description' => ['type' => 'string', 'desc' => 'Customer message copy', 'example' => 'Thank you for reaching out...'],
                ],
                'sample_data' => [
                    'customer_name' => 'Sarah Connor',
                    'subject' => 'Enterprise Inquiries',
                    'description' => 'Looking forward to hearing from your enterprise onboarding specialist.',
                ],
                'factory' => function (array $data = []) {
                    $inquiry = self::getSampleInquiry($data);
                    return new \App\Mail\ClientInquiryConfirmation($inquiry);
                }
            ],
            'ClientPaymentReceivedMail' => [
                'title' => 'Client Receipt: Payment Received (Blade)',
                'class' => \App\Mail\ClientPaymentReceivedMail::class,
                'view' => 'emails.tenant.payment_received',
                'category' => 'Billing & Payments',
                'description' => 'Sent to client upon successful subscription or invoice payment with invoice receipt.',
                'variables' => [
                    '$tenant->business_name' => ['type' => 'string', 'desc' => 'Tenant business name', 'example' => 'Acme Global Ventures'],
                    '$payment->amount' => ['type' => 'numeric', 'desc' => 'Paid amount', 'example' => 499.00],
                    '$payment->currency' => ['type' => 'string', 'desc' => 'Currency code', 'example' => 'USD'],
                    '$payment->order_id' => ['type' => 'string', 'desc' => 'Order identifier', 'example' => 'ORD-89214'],
                    '$payment->payment_method' => ['type' => 'string', 'desc' => 'Gateway or card method', 'example' => 'Credit Card (Stripe)'],
                ],
                'sample_data' => [
                    'business_name' => 'Acme Global Ventures',
                    'amount' => 499.00,
                    'currency' => 'USD',
                    'order_id' => 'ORD-89214',
                    'payment_method' => 'Credit Card (Stripe)',
                ],
                'factory' => function (array $data = []) {
                    $tenant = self::getSampleTenant(['business_name' => $data['business_name'] ?? 'Acme Global Ventures']);
                    $payment = self::getSamplePayment([
                        'amount' => $data['amount'] ?? 499.00,
                        'currency' => $data['currency'] ?? 'USD',
                        'order_id' => $data['order_id'] ?? 'ORD-89214',
                        'payment_method' => $data['payment_method'] ?? 'Credit Card (Stripe)',
                    ]);
                    return new \App\Mail\ClientPaymentReceivedMail($tenant, $payment);
                }
            ],
            'AdminPaymentReceivedMail' => [
                'title' => 'Admin Alert: Payment Received (Blade)',
                'class' => \App\Mail\AdminPaymentReceivedMail::class,
                'view' => 'emails.admin.payment_received',
                'category' => 'Billing & Payments',
                'description' => 'Alerts admins when a client makes a successful payment.',
                'variables' => [
                    '$tenant->business_name' => ['type' => 'string', 'desc' => 'Tenant business name', 'example' => 'Acme Global Ventures'],
                    '$payment->amount' => ['type' => 'numeric', 'desc' => 'Paid sum', 'example' => 499.00],
                    '$payment->currency' => ['type' => 'string', 'desc' => 'Currency code', 'example' => 'USD'],
                    '$payment->order_id' => ['type' => 'string', 'desc' => 'Order reference', 'example' => 'ORD-89214'],
                ],
                'sample_data' => [
                    'business_name' => 'Acme Global Ventures',
                    'amount' => 499.00,
                    'currency' => 'USD',
                    'order_id' => 'ORD-89214',
                ],
                'factory' => function (array $data = []) {
                    $tenant = self::getSampleTenant(['business_name' => $data['business_name'] ?? 'Acme Global Ventures']);
                    $payment = self::getSamplePayment([
                        'amount' => $data['amount'] ?? 499.00,
                        'currency' => $data['currency'] ?? 'USD',
                        'order_id' => $data['order_id'] ?? 'ORD-89214',
                    ]);
                    return new \App\Mail\AdminPaymentReceivedMail($tenant, $payment);
                }
            ],
            'ClientPaymentFailedMail' => [
                'title' => 'Client Alert: Payment Failed (Blade)',
                'class' => \App\Mail\ClientPaymentFailedMail::class,
                'view' => 'emails.tenant.payment_failed',
                'category' => 'Billing & Payments',
                'description' => 'Sent to client when a recurring or checkout charge fails.',
                'variables' => [
                    '$tenant->business_name' => ['type' => 'string', 'desc' => 'Tenant business name', 'example' => 'Acme Global Ventures'],
                    '$payment->amount' => ['type' => 'numeric', 'desc' => 'Attempted charge', 'example' => 499.00],
                    '$payment->order_id' => ['type' => 'string', 'desc' => 'Order identifier', 'example' => 'ORD-89214'],
                ],
                'sample_data' => [
                    'business_name' => 'Acme Global Ventures',
                    'amount' => 499.00,
                    'order_id' => 'ORD-89214',
                ],
                'factory' => function (array $data = []) {
                    $tenant = self::getSampleTenant(['business_name' => $data['business_name'] ?? 'Acme Global Ventures']);
                    $payment = self::getSamplePayment([
                        'amount' => $data['amount'] ?? 499.00,
                        'order_id' => $data['order_id'] ?? 'ORD-89214',
                    ]);
                    return new \App\Mail\ClientPaymentFailedMail($tenant, $payment);
                }
            ],
            'AdminPaymentFailedMail' => [
                'title' => 'Admin Alert: Payment Failed (Blade)',
                'class' => \App\Mail\AdminPaymentFailedMail::class,
                'view' => 'emails.admin.payment_failed',
                'category' => 'Billing & Payments',
                'description' => 'Notifies admins of a failed payment attempt.',
                'variables' => [
                    '$tenant->business_name' => ['type' => 'string', 'desc' => 'Tenant company', 'example' => 'Acme Global Ventures'],
                    '$payment->amount' => ['type' => 'numeric', 'desc' => 'Attempted sum', 'example' => 499.00],
                    '$payment->order_id' => ['type' => 'string', 'desc' => 'Order reference', 'example' => 'ORD-89214'],
                ],
                'sample_data' => [
                    'business_name' => 'Acme Global Ventures',
                    'amount' => 499.00,
                    'order_id' => 'ORD-89214',
                ],
                'factory' => function (array $data = []) {
                    $tenant = self::getSampleTenant(['business_name' => $data['business_name'] ?? 'Acme Global Ventures']);
                    $payment = self::getSamplePayment([
                        'amount' => $data['amount'] ?? 499.00,
                        'order_id' => $data['order_id'] ?? 'ORD-89214',
                    ]);
                    return new \App\Mail\AdminPaymentFailedMail($tenant, $payment);
                }
            ],
            'TenantProvisionedEmail' => [
                'title' => 'Tenant Provisioned & Credentials Ready (Blade)',
                'class' => \App\Mail\TenantProvisionedEmail::class,
                'view' => 'emails.tenant.provisioned',
                'category' => 'Tenant Provisioning',
                'description' => 'Sent to client when their multi-tenant instance has finished provisioning with root admin login credentials.',
                'variables' => [
                    '$tenant->business_name' => ['type' => 'string', 'desc' => 'Client company name', 'example' => 'Acme Global Ventures'],
                    '$adminEmail' => ['type' => 'string', 'desc' => 'Root admin email address', 'example' => 'admin@acmeglobal.com'],
                    '$adminPassword' => ['type' => 'string', 'desc' => 'Initial root password', 'example' => 'SecretP@ss123!'],
                    '$domainUrl' => ['type' => 'url', 'desc' => 'Live website / admin URL', 'example' => 'https://portal.acmeglobal.com'],
                ],
                'sample_data' => [
                    'business_name' => 'Acme Global Ventures',
                    'adminEmail' => 'admin@acmeglobal.com',
                    'adminPassword' => 'SecretP@ss123!',
                    'domainUrl' => 'https://portal.acmeglobal.com',
                ],
                'factory' => function (array $data = []) {
                    $tenant = self::getSampleTenant(['business_name' => $data['business_name'] ?? 'Acme Global Ventures']);
                    $adminEmail = $data['adminEmail'] ?? 'admin@acmeglobal.com';
                    $adminPassword = $data['adminPassword'] ?? 'SecretP@ss123!';
                    $domainUrl = $data['domainUrl'] ?? 'https://portal.acmeglobal.com';
                    return new \App\Mail\TenantProvisionedEmail($tenant, $adminEmail, $adminPassword, $domainUrl);
                }
            ],
            'TenantSetupReadyMail' => [
                'title' => 'Tenant Setup Ready (Blade)',
                'class' => \App\Mail\TenantSetupReadyMail::class,
                'view' => 'emails.tenant.setup_ready',
                'category' => 'Tenant Provisioning',
                'description' => 'Sent to client informing them their environment is ready for onboarding configuration.',
                'variables' => [
                    '$tenant->business_name' => ['type' => 'string', 'desc' => 'Business name', 'example' => 'Acme Global Ventures'],
                    '$tenant->subdomain' => ['type' => 'string', 'desc' => 'Subdomain pointer', 'example' => 'acme-store'],
                ],
                'sample_data' => [
                    'business_name' => 'Acme Global Ventures',
                    'subdomain' => 'acme-store',
                ],
                'factory' => function (array $data = []) {
                    $tenant = self::getSampleTenant([
                        'business_name' => $data['business_name'] ?? 'Acme Global Ventures',
                        'subdomain' => $data['subdomain'] ?? 'acme-store',
                    ]);
                    return new \App\Mail\TenantSetupReadyMail($tenant);
                }
            ],
            'CustomDomainDnsSetupMail' => [
                'title' => 'Custom Domain DNS Instructions (Blade)',
                'class' => \App\Mail\CustomDomainDnsSetupMail::class,
                'view' => 'emails.custom_domain_dns_setup',
                'category' => 'Domain & DNS',
                'description' => 'Instructions sent to customers explaining what A and CNAME records to add at their registrar.',
                'variables' => [
                    '$domain->domain' => ['type' => 'string', 'desc' => 'Custom domain name', 'example' => 'portal.acmeglobal.com'],
                    '$serverIp' => ['type' => 'string', 'desc' => 'Server Public IP address', 'example' => '159.89.172.95'],
                    '$dnsRecords' => ['type' => 'array', 'desc' => 'List of DNS records to add', 'example' => 'A and CNAME records'],
                ],
                'sample_data' => [
                    'domain' => 'portal.acmeglobal.com',
                    'serverIp' => '159.89.172.95',
                ],
                'factory' => function (array $data = []) {
                    $tenant = self::getSampleTenant();
                    $domain = self::getSampleDomain(['domain' => $data['domain'] ?? 'portal.acmeglobal.com']);
                    $serverIp = $data['serverIp'] ?? '159.89.172.95';
                    $dnsRecords = [
                        [
                            'type' => 'A',
                            'name' => '@',
                            'host' => '@',
                            'value' => $serverIp,
                            'ttl' => 300
                        ],
                        [
                            'type' => 'CNAME',
                            'name' => 'www',
                            'host' => 'www',
                            'value' => $domain->domain,
                            'ttl' => 300
                        ]
                    ];
                    return new \App\Mail\CustomDomainDnsSetupMail($tenant, $domain, $serverIp, $dnsRecords);
                }
            ],
            'SubscriptionExpiryEmail' => [
                'title' => 'Subscription Expiry Notice (Blade)',
                'class' => \App\Mail\SubscriptionExpiryEmail::class,
                'view' => 'emails.tenant.subscription_expiry',
                'category' => 'Billing & Payments',
                'description' => 'Notice sent when a tenant subscription is nearing expiration or has expired.',
                'variables' => [
                    '$tenant->business_name' => ['type' => 'string', 'desc' => 'Tenant business name', 'example' => 'Acme Global Ventures'],
                    '$title' => ['type' => 'string', 'desc' => 'Notification headline', 'example' => 'Action Required: Your Subscription is Expiring'],
                    '$messageStr' => ['type' => 'string', 'desc' => 'Expiry details and instructions', 'example' => 'Your subscription will lapse in 3 days.'],
                ],
                'sample_data' => [
                    'business_name' => 'Acme Global Ventures',
                    'title' => 'Action Required: Subscription Expiring Soon',
                    'messageStr' => 'Your subscription for Acme Global Ventures will expire on ' . Carbon::now()->addDays(3)->format('F d, Y') . '. Please renew your plan to prevent service interruption.',
                ],
                'factory' => function (array $data = []) {
                    $tenant = self::getSampleTenant(['business_name' => $data['business_name'] ?? 'Acme Global Ventures']);
                    $title = $data['title'] ?? 'Action Required: Subscription Expiring Soon';
                    $messageStr = $data['messageStr'] ?? 'Your subscription will expire soon. Please renew promptly.';
                    return new \App\Mail\SubscriptionExpiryEmail($tenant, $title, $messageStr);
                }
            ],
            'AdminNewPurchaseMail' => [
                'title' => 'Admin Alert: New Checkout Initiated (Blade/HTML)',
                'class' => \App\Mail\AdminNewPurchaseMail::class,
                'view' => 'inline HTML',
                'category' => 'Billing & Payments',
                'description' => 'Sent to admin when a user initiates a checkout purchase.',
                'variables' => [
                    '$user->name' => ['type' => 'string', 'desc' => 'Client name', 'example' => 'Alex Morgan'],
                    '$tenant->business_name' => ['type' => 'string', 'desc' => 'Business name', 'example' => 'Acme Global Ventures'],
                    '$tenant->uuid' => ['type' => 'string', 'desc' => 'Tenant UUID', 'example' => 'tnt_abc12345'],
                ],
                'sample_data' => [
                    'user_name' => 'Alex Morgan',
                    'business_name' => 'Acme Global Ventures',
                ],
                'factory' => function (array $data = []) {
                    $tenant = self::getSampleTenant(['business_name' => $data['business_name'] ?? 'Acme Global Ventures']);
                    $user = self::getSampleUser(['name' => $data['user_name'] ?? 'Alex Morgan']);
                    return new \App\Mail\AdminNewPurchaseMail($tenant, $user);
                }
            ],
            'TenantSuspendedView' => [
                'title' => 'Account Suspended Notice (Blade View)',
                'class' => null,
                'view' => 'emails.tenant.suspended',
                'category' => 'Tenant Management',
                'description' => 'Dedicated blade template rendered when an account is temporarily suspended.',
                'variables' => [
                    '$clientName' => ['type' => 'string', 'desc' => 'Customer name', 'example' => 'Alex Morgan'],
                    '$suspensionReason' => ['type' => 'string', 'desc' => 'Reason for suspension', 'example' => 'Overdue renewal payment'],
                    '$settings' => ['type' => 'array', 'desc' => 'Application branding settings', 'example' => 'Company logo and name'],
                ],
                'sample_data' => [
                    'clientName' => 'Alex Morgan',
                    'suspensionReason' => 'Overdue invoice payment / subscription grace period exceeded.',
                ],
                'factory' => function (array $data = []) {
                    $settings = self::getSampleSettings();
                    $clientName = $data['clientName'] ?? 'Alex Morgan';
                    $suspensionReason = $data['suspensionReason'] ?? 'Overdue subscription renewal.';
                    return view('emails.tenant.suspended', [
                        'clientName' => $clientName,
                        'suspensionReason' => $suspensionReason,
                        'settings' => $settings,
                        'tenant' => self::getSampleTenant(),
                        'hasAppImage' => false,
                        'companyName' => $settings['company_name'] ?? 'TidCraft',
                    ])->render();
                }
            ],
        ];
    }
}
