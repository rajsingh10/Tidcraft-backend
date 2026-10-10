<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;

class GenerateSslForCustomDomainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tenant;
    protected $domain;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Tenant $tenant, string $domain)
    {
        $this->tenant = $tenant;
        $this->domain = $domain;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $currentUser = exec('whoami');
        Log::info("GenerateSslForCustomDomainJob: Starting SSL generation for domain {$this->domain}. Running as user: {$currentUser}");

        try {
            // STEP 1: Generate HTTP-only configuration to pass Let's Encrypt webroot challenge
            $httpConfig = $this->generateHttpConfig();
            $tmpFile = '/tmp/' . $this->domain . '.conf';
            file_put_contents($tmpFile, $httpConfig);
            
            $sitesAvailablePath = "/etc/nginx/sites-available/{$this->domain}.conf";
            $sitesEnabledPath = "/etc/nginx/sites-enabled/{$this->domain}.conf";
            
            $this->runCommand("sudo /bin/cp {$tmpFile} {$sitesAvailablePath}");
            $this->runCommand("sudo /bin/ln -sf {$sitesAvailablePath} {$sitesEnabledPath}");
            $this->runCommand("sudo /bin/systemctl reload nginx");
            
            // STEP 2: Run Certbot in certonly mode using webroot plugin (no Nginx auto-configuration)
            $adminEmail = env('ADMIN_EMAIL', 'admin@tidcraft.com');
            $tenantsDir = env('TENANTS_DIRECTORY', '/home/prodtidcraftcomusr/tenants/');
            $webrootPath = rtrim($tenantsDir, '/') . "/{$this->domain}/public";
            
            // Ensure the webroot directory exists before running Certbot
            $this->runCommand("sudo /bin/mkdir -p {$webrootPath}");
            
            $certbotCmd = "if [ -x /usr/bin/certbot ]; then sudo /usr/bin/certbot certonly --webroot -w {$webrootPath} -d {$this->domain} --cert-name {$this->domain} -m {$adminEmail} --agree-tos --non-interactive; else sudo /snap/bin/certbot certonly --webroot -w {$webrootPath} -d {$this->domain} --cert-name {$this->domain} -m {$adminEmail} --agree-tos --non-interactive; fi";
            
            Log::info("GenerateSslForCustomDomainJob: Running Certbot Webroot: {$certbotCmd}");
            $certbotOutput = $this->runCommand($certbotCmd);
            
            // Allow success if certificate already exists or successfully received
            if (stripos($certbotOutput, 'Successfully received certificate') === false && stripos($certbotOutput, 'Certificate not yet due for renewal') === false) {
                if (stripos($certbotOutput, 'error') !== false || stripos($certbotOutput, 'failed') !== false) {
                    throw new \Exception("Certbot certonly failed: " . $certbotOutput);
                }
            }

            // STEP 3: Generate the final HTTPS configuration now that certificates exist
            $httpsConfig = $this->generateHttpsConfig();
            file_put_contents($tmpFile, $httpsConfig);
            
            $this->runCommand("sudo /bin/cp {$tmpFile} {$sitesAvailablePath}");
            $this->runCommand("sudo /bin/systemctl reload nginx");
            
            // Clean up temp file
            @unlink($tmpFile);
            
            Log::info("GenerateSslForCustomDomainJob: Successfully generated SSL for domain {$this->domain}");
            
            $domainModel = \App\Models\Domain::where('tenant_id', $this->tenant->id)->where('domain', $this->domain)->first();
            if ($domainModel) {
                $domainModel->update([
                    'ssl_verified' => true,
                    'ssl_verified_at' => now(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error("GenerateSslForCustomDomainJob: Failed to generate SSL for {$this->domain}. Error: " . $e->getMessage());
            
            // Revert domain verification status if it fails
            $domainModel = \App\Models\Domain::where('tenant_id', $this->tenant->id)->where('domain', $this->domain)->first();
            if ($domainModel) {
                $domainModel->update([
                    'ssl_verified' => false,
                    'ssl_verified_at' => null,
                ]);
            }
        }
    }

    private function generateHttpConfig(): string
    {
        $tenantsDir = env('TENANTS_DIRECTORY', '/home/prodtidcraftcomusr/tenants');
        $tenantsDir = rtrim($tenantsDir, '/');
        
        return <<<EOT
server {
    listen 80;
    listen [::]:80;
    
    server_name {$this->domain};

    root {$tenantsDir}/{$this->domain}/public;
    index index.html index.php;
    charset utf-8;

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOT;
    }

    private function generateHttpsConfig(): string
    {
        $tenantsDir = env('TENANTS_DIRECTORY', '/home/prodtidcraftcomusr/tenants');
        $tenantsDir = rtrim($tenantsDir, '/');
        
        return <<<EOT
server {
    listen 80;
    listen [::]:80;
    server_name {$this->domain};
    
    # Redirect all HTTP requests to HTTPS
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    
    server_name {$this->domain};
    root {$tenantsDir}/{$this->domain}/public;
    index index.html index.php;
    charset utf-8;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    client_max_body_size 50M;

    ssl_certificate /etc/letsencrypt/live/{$this->domain}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/{$this->domain}/privkey.pem;
    
    # Advanced SSL settings
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384;

    error_page 404 /index.php;

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOT;
    }

    private function runCommand(string $command): void
    {
        Log::info("GenerateSslForCustomDomainJob: Executing command: {$command}");
        exec($command . ' 2>&1', $output, $returnVar);
        $outputStr = implode("\n", $output);
        Log::info("GenerateSslForCustomDomainJob: Command output: " . ($outputStr ?: 'No output'));
        
        if ($returnVar !== 0) {
            throw new \Exception("Command failed with exit code {$returnVar}. Output: {$outputStr}");
        }
    }
}
