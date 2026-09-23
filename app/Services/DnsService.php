<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\EmailTemplate;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Mail\CustomDomainDnsSetupMail;
use App\Mail\DynamicEmail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DnsService
{
    /**
     * Auto-detect the server's public IP address with fallback priority:
     * 1. DB Setting: Setting::where('key', 'server_ip')->value('value')
     * 2. Environment: env('SERVER_IP')
     * 3. Server Header: $_SERVER['SERVER_ADDR'] (if public IP)
     * 4. Dynamic Auto-detection (Cached 24h):
     *    - If non-local: query https://api.ipify.org
     *    - Resolve app host from config('app.url')
     *    - Resolve devapi.tidcraft.com (13.61.223.5)
     *    - Resolve tidcraft.com (13.63.115.67)
     *    - Fallback: 13.61.223.5
     */
    public static function getServerIp(): string
    {
        // 1. Check DB Setting override
        try {
            $dbIp = Setting::where('key', 'server_ip')->value('value');
            if (!empty($dbIp) && filter_var(trim($dbIp), FILTER_VALIDATE_IP)) {
                return trim($dbIp);
            }
        } catch (\Throwable $e) {
            // DB not ready or settings table missing
        }

        // 2. Check Environment Variable
        $envIp = env('SERVER_IP');
        if (!empty($envIp) && filter_var(trim($envIp), FILTER_VALIDATE_IP)) {
            return trim($envIp);
        }

        // 3. Check SERVER_ADDR if public IP
        $serverAddr = request()->server('SERVER_ADDR') ?? ($_SERVER['SERVER_ADDR'] ?? null);
        if (!empty($serverAddr) && filter_var($serverAddr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return trim($serverAddr);
        }

        // 4. Cached dynamic auto-detection
        return Cache::remember('auto_detected_server_ip', 86400, function () {
            // If running on a cloud VPS/server (not local development), query public IP service
            if (!app()->environment('local')) {
                try {
                    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
                    $detected = @file_get_contents('https://api.ipify.org', false, $ctx);
                    if ($detected && filter_var(trim($detected), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return trim($detected);
                    }
                } catch (\Throwable $e) {
                    // Ignore and continue fallbacks
                }
            }

            // Check host from config('app.url') if not localhost
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);
            if (!empty($appHost) && !in_array(strtolower($appHost), ['localhost', '127.0.0.1'])) {
                $resolved = @gethostbyname($appHost);
                if ($resolved && $resolved !== $appHost && filter_var($resolved, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $resolved;
                }
            }

            // Fallback: Resolve devapi.tidcraft.com (the API server domain)
            $resolvedDev = @gethostbyname('devapi.tidcraft.com');
            if ($resolvedDev && filter_var($resolvedDev, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $resolvedDev;
            }

            // Fallback: Resolve tidcraft.com
            $resolvedMain = @gethostbyname('tidcraft.com');
            if ($resolvedMain && filter_var($resolvedMain, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $resolvedMain;
            }

            // Ultimate fallback (Devapi server IP)
            return '13.61.223.5';
        });
    }

    /**
     * Clean and normalize a domain string.
     */
    public static function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);
        $domain = preg_replace('#^https?://#i', '', $domain);
        $domain = rtrim($domain, '/');
        $domain = preg_replace('/:[0-9]+$/', '', $domain);
        return strtolower($domain);
    }

    /**
     * Check whether domain is an apex/root domain (e.g. ratak.com) or a subdomain (e.g. shop.ratak.com).
     */
    public static function isApexDomain(string $domain): bool
    {
        $domain = self::normalizeDomain($domain);
        $parts = explode('.', $domain);
        
        // Two parts e.g. domain.com
        if (count($parts) === 2) {
            return true;
        }

        // Handle common two-part TLDs like .co.uk, .com.au, .co.in
        $commonTwoPartTlds = ['co.uk', 'com.au', 'co.in', 'org.uk', 'net.au', 'co.nz'];
        if (count($parts) === 3) {
            $lastTwo = $parts[1] . '.' . $parts[2];
            if (in_array($lastTwo, $commonTwoPartTlds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate required DNS records for the domain.
     */
    public static function getExpectedDnsRecords(string $domain, string $domainType = 'custom'): array
    {
        $domain = self::normalizeDomain($domain);

        if ($domainType === 'subdomain') {
            return [
                [
                    'type' => 'CNAME',
                    'name' => explode('.', $domain)[0] ?? '@',
                    'host' => $domain,
                    'value' => 'Internal Tidcraft Routing',
                    'ttl' => 3600,
                    'description' => 'Subdomain managed automatically by Tidcraft internal routing.'
                ]
            ];
        }

        $serverIp = self::getServerIp();
        $isApex = self::isApexDomain($domain);

        if ($isApex) {
            return [
                [
                    'type' => 'A',
                    'name' => '@',
                    'host' => $domain,
                    'value' => $serverIp,
                    'ttl' => 3600,
                    'status' => 'required',
                    'description' => "Points your apex/root domain (@) directly to Tidcraft server IP ({$serverIp})."
                ],
                [
                    'type' => 'CNAME',
                    'name' => 'www',
                    'host' => 'www.' . $domain,
                    'value' => $domain,
                    'ttl' => 3600,
                    'status' => 'recommended',
                    'description' => "Redirects www.{$domain} to your root domain {$domain}."
                ]
            ];
        }

        // Subdomain of a custom domain (e.g. order.ratak.com)
        $subPart = explode('.', $domain)[0];
        return [
            [
                'type' => 'A',
                'name' => $subPart,
                'host' => $domain,
                'value' => $serverIp,
                'ttl' => 3600,
                'status' => 'required',
                'description' => "Points {$domain} directly to Tidcraft server IP ({$serverIp})."
            ],
            [
                'type' => 'CNAME',
                'name' => $subPart,
                'host' => $domain,
                'value' => 'devapi.tidcraft.com',
                'ttl' => 3600,
                'status' => 'alternative',
                'description' => "Alternative: Point CNAME to Tidcraft host (devapi.tidcraft.com)."
            ]
        ];
    }

    /**
     * Verify live DNS resolution for a domain.
     */
    public static function verifyDomainDns(string $domain, string $domainType = 'custom'): array
    {
        $domain = self::normalizeDomain($domain);

        // Subdomains managed by Tidcraft are automatically verified
        if ($domainType === 'subdomain') {
            return [
                'verified' => true,
                'domain' => $domain,
                'server_ip' => self::getServerIp(),
                'resolved_ips' => [],
                'message' => 'Tidcraft subdomains are managed internally and always active.'
            ];
        }

        $serverIp = self::getServerIp();
        $resolvedIps = @gethostbynamel($domain) ?: [];

        // Check A records
        $aRecords = @dns_get_record($domain, DNS_A) ?: [];
        foreach ($aRecords as $rec) {
            if (isset($rec['ip']) && !in_array($rec['ip'], $resolvedIps)) {
                $resolvedIps[] = $rec['ip'];
            }
        }

        // Check CNAME records
        $cnameRecords = @dns_get_record($domain, DNS_CNAME) ?: [];
        $cnameTargets = [];
        foreach ($cnameRecords as $rec) {
            if (isset($rec['target'])) {
                $cnameTargets[] = $rec['target'];
                $cnameIps = @gethostbynamel($rec['target']) ?: [];
                $resolvedIps = array_merge($resolvedIps, $cnameIps);
            }
        }
        $resolvedIps = array_values(array_unique(array_filter($resolvedIps)));

        // Test if server IP is among the resolved IPs
        $isVerified = in_array($serverIp, $resolvedIps, true);

        return [
            'verified' => $isVerified,
            'domain' => $domain,
            'server_ip' => $serverIp,
            'resolved_ips' => $resolvedIps,
            'cname_targets' => $cnameTargets,
            'message' => $isVerified
                ? "DNS successfully verified! Your domain is pointing to Tidcraft server ({$serverIp})."
                : "DNS records have not propagated yet or are pointing to different IP(s). Expected: {$serverIp}. Found: " . (empty($resolvedIps) ? 'None' : implode(', ', $resolvedIps))
        ];
    }

    /**
     * Create Nginx symlink for tenant frontend routing.
     */
    public static function createTenantSymlink(Tenant $tenant, string $domainName): bool
    {
        try {
            $product = Product::find($tenant->product_id);
            if (!$product || empty($product->frontend_path)) {
                return false;
            }

            $tenantsDirectory = env('TENANTS_DIRECTORY', '/home/devtidcraftcomusr/tenants/');
            if (!file_exists($tenantsDirectory)) {
                @mkdir($tenantsDirectory, 0755, true);
            }

            $targetPath = $product->frontend_path;
            if (!file_exists($targetPath)) {
                Log::warning("DnsService: Target frontend path does not exist: {$targetPath}");
                return false;
            }

            // 1. Symlink for primary domain
            $symlinkPath = rtrim($tenantsDirectory, '/') . '/' . $domainName;
            self::ensureSymlink($symlinkPath, $targetPath);

            // 2. If apex domain, also create symlink for www.domain
            if (self::isApexDomain($domainName)) {
                $wwwSymlink = rtrim($tenantsDirectory, '/') . '/www.' . $domainName;
                self::ensureSymlink($wwwSymlink, $targetPath);
            }

            return true;
        } catch (\Throwable $e) {
            Log::error("DnsService::createTenantSymlink error: " . $e->getMessage());
            return false;
        }
    }

    private static function ensureSymlink(string $symlinkPath, string $targetPath): void
    {
        if (is_link($symlinkPath)) {
            $current = @readlink($symlinkPath);
            if ($current !== $targetPath) {
                @unlink($symlinkPath);
                @symlink($targetPath, $symlinkPath);
            }
        } elseif (!file_exists($symlinkPath)) {
            @symlink($targetPath, $symlinkPath);
        }
    }

    /**
     * Send DNS setup instructions email to the client.
     */
    public static function sendDnsInstructionsEmail(Tenant $tenant, Domain $domain, bool $force = false): bool
    {
        try {
            // Find client email
            $client = $tenant->client ?? User::find($tenant->client_id ?? $tenant->create_by);
            $clientEmail = $client->email ?? $tenant->primary_contact_email;
            $clientName = $client->name ?? $tenant->business_name ?? 'Valued Client';

            if (empty($clientEmail)) {
                Log::warning("DnsService::sendDnsInstructionsEmail: No recipient email found for Tenant ID {$tenant->id}");
                return false;
            }

            // Deduplication: prevent sending duplicate DNS instructions for the same tenant & domain within 10 minutes unless forced
            $cleanDomain = strtolower(trim($domain->domain));
            $cacheKey = "dns_instructions_sent_{$tenant->id}_{$cleanDomain}";
            if (!$force && Cache::has($cacheKey)) {
                Log::info("DnsService: DNS instructions email already sent recently to {$clientEmail} for domain {$cleanDomain}. Skipping duplicate.");
                return true;
            }

            $serverIp = self::getServerIp();
            $dnsRecords = self::getExpectedDnsRecords($domain->domain, $domain->type);

            // Check if dynamic EmailTemplate exists and is active
            $template = EmailTemplate::whereIn('slug', ['Custom_Domain_Dns_Setup', 'custom_domain_dns_setup'])->first();

            if ($template && $template->status === 'active') {
                $dnsTableHtml = self::buildDnsTableHtml($dnsRecords);

                $replacements = [
                    '{name}' => $clientName,
                    '{{name}}' => $clientName,
                    '{client_name}' => $clientName,
                    '{{client_name}}' => $clientName,
                    '{business_name}' => $tenant->business_name,
                    '{{business_name}}' => $tenant->business_name,
                    '{product_name}' => $tenant->product->name ?? 'Application',
                    '{{product_name}}' => $tenant->product->name ?? 'Application',
                    '{domain}' => $domain->domain,
                    '{{domain}}' => $domain->domain,
                    '{server_ip}' => $serverIp,
                    '{{server_ip}}' => $serverIp,
                    '{dns_records_table}' => $dnsTableHtml,
                    '{{dns_records_table}}' => $dnsTableHtml,
                    '{verify_url}' => url('/client/purchases/' . $tenant->uuid),
                    '{{verify_url}}' => url('/client/purchases/' . $tenant->uuid),
                    '{portal_url}' => url('/client/purchases/' . $tenant->uuid),
                    '{{portal_url}}' => url('/client/purchases/' . $tenant->uuid),
                    'tenant' => $tenant,
                    'domain' => $domain,
                ];

                Mail::to($clientEmail)->send(new DynamicEmail($template, $replacements));
            } else {
                // Fallback to rich blade template
                Mail::to($clientEmail)->send(new CustomDomainDnsSetupMail($tenant, $domain, $serverIp, $dnsRecords));
            }

            Cache::put($cacheKey, true, now()->addMinutes(10));

            Log::info("DnsService: Sent DNS instructions email to {$clientEmail} for domain {$domain->domain}");
            return true;
        } catch (\Throwable $e) {
            Log::error("DnsService::sendDnsInstructionsEmail failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Build an HTML table of DNS records for dynamic email replacements.
     */
    public static function buildDnsTableHtml(array $dnsRecords): string
    {
        $html = '<table style="width:100%; border-collapse:collapse; margin:20px 0; font-size:14px;">';
        $html .= '<thead><tr style="background:#f1f5f9; text-align:left;">';
        $html .= '<th style="padding:10px; border:1px solid #e2e8f0;">Record Type</th>';
        $html .= '<th style="padding:10px; border:1px solid #e2e8f0;">Host / Name</th>';
        $html .= '<th style="padding:10px; border:1px solid #e2e8f0;">Points To / Value</th>';
        $html .= '<th style="padding:10px; border:1px solid #e2e8f0;">TTL</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($dnsRecords as $record) {
            $html .= '<tr>';
            $html .= '<td style="padding:10px; border:1px solid #e2e8f0; font-weight:bold; color:#2563eb;">' . htmlspecialchars($record['type']) . '</td>';
            $html .= '<td style="padding:10px; border:1px solid #e2e8f0;"><code>' . htmlspecialchars($record['name']) . '</code></td>';
            $html .= '<td style="padding:10px; border:1px solid #e2e8f0; font-weight:600;"><code>' . htmlspecialchars($record['value']) . '</code></td>';
            $html .= '<td style="padding:10px; border:1px solid #e2e8f0;">' . htmlspecialchars((string)$record['ttl']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }
}
