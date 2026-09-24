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
        Log::info("GenerateSslForCustomDomainJob: Starting SSL generation for domain {$this->domain}");

        try {
            $nginxConfig = $this->generateNginxConfig();
            
            // 1. Write the config to a temporary file
            $tmpFile = '/tmp/' . $this->domain . '.conf';
            file_put_contents($tmpFile, $nginxConfig);
            
            // 2. Move to sites-available using sudo
            $sitesAvailablePath = "/etc/nginx/sites-available/{$this->domain}.conf";
            $sitesEnabledPath = "/etc/nginx/sites-enabled/{$this->domain}.conf";
            
            $this->runCommand("sudo /bin/cp {$tmpFile} {$sitesAvailablePath}");
            
            // 3. Create symlink
            $this->runCommand("sudo /bin/ln -sf {$sitesAvailablePath} {$sitesEnabledPath}");
            
            // 4. Reload Nginx so the HTTP block is active
            $this->runCommand("sudo /bin/systemctl reload nginx");
            
            // 5. Run Certbot to generate the certificate and automatically upgrade the Nginx config
            $adminEmail = env('ADMIN_EMAIL', 'admin@tidcraft.com');
            
            // Try both common paths for certbot (apt vs snap)
            $certbotCmd = "if [ -x /usr/bin/certbot ]; then sudo /usr/bin/certbot --nginx -d {$this->domain} -m {$adminEmail} --agree-tos --non-interactive --redirect; else sudo /snap/bin/certbot --nginx -d {$this->domain} -m {$adminEmail} --agree-tos --non-interactive --redirect; fi";
            
            Log::info("GenerateSslForCustomDomainJob: Running Certbot: {$certbotCmd}");
            $this->runCommand($certbotCmd);
            
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
        }
    }

    private function generateNginxConfig(): string
    {
        return <<<EOT
server {
    listen 80;
    listen [::]:80;
    
    server_name {$this->domain};

    root /home/devtidcraftcomusr/tenants/{$this->domain};
    index index.html index.php;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.html;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
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
