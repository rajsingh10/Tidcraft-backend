<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\EmailTemplate;
use App\Models\Setting;
use Illuminate\Support\Facades\Blade;

class DynamicEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $template;
    public $dynamicContent;
    public $dynamicSubject;
    public $settingsData;
    public $emailAttachments = [];

    /**
     * Create a new message instance.
     *
     * @param EmailTemplate $template
     * @param array $replacements Key-Value pairs to replace in the content and subject
     * @param array $attachments Array of attachments: [['data' => $bytes, 'name' => '...', 'mime' => '...']]
     */
    public function __construct(EmailTemplate $template, array $replacements = [], array $attachments = [])
    {
        $this->template = $template;
        $this->emailAttachments = $attachments;

        // Replace variables in subject
        $subject = $template->subject;
        
        // Replace variables in content
        $content = html_entity_decode($template->content, ENT_QUOTES | ENT_HTML5, 'UTF-8'); // Decode in case WYSIWYG encoded tags
        
        // WYSIWYG editors often inject <p>, <br>, or other HTML tags inside @php blocks, causing Blade parse errors.
        // We strip all HTML tags exclusively from inside @php ... @endphp blocks.
        $content = preg_replace_callback('/@php(.*?)@endphp/is', function($matches) {
            return '@php' . strip_tags($matches[1]) . '@endphp';
        }, $content);

        // Frontend WYSIWYG editor protects Blade tags inside HTML attributes by wrapping them in <!-- [LARAVEL_VAR_{base64}] -->
        // We must decode both normal and HTML-escaped comments before compiling with Blade.
        // Also strip any mangled '=""' around comments and stray duplicate '>' after HTML opening tags (e.g. <div class="step-row" ...>>).
        $content = preg_replace_callback('/(?:<!--|&lt;!--)(?:="")?\s*\[(?:LARAVEL_VAR_|laravel_var_)([a-zA-Z0-9+\/]+=*)\](?:="")?\s*(?:-->|--&gt;)>?/i', function($matches) {
            return base64_decode($matches[1]);
        }, $content);

        // Clean up any remaining duplicate closing angle brackets inside opening tags like <div ... >>
        $content = preg_replace('/(<[a-zA-Z0-9\-]+(?:\s+[^>]*?)?)>>/i', '$1>', $content);

        // Decode any URL-encoded Blade tags (e.g. %7B%7B ... %7D%7D) created by WYSIWYG or DOM serialization
        $content = preg_replace_callback('/%7B%7B(.*?)%7D%7D/i', function($matches) {
            return '{{' . urldecode($matches[1]) . '}}';
        }, $content);

        // Fix specifically mangled blade tags caused by single quotes inside double-quoted HTML attributes in WYSIWYG:
        // 1. Mangled company_logo tag (e.g. {{ url($settings[" company_logo'])="" }}"="")
        $content = preg_replace(
            '/\{\{\s*url\(\$settings\[["\'\s]*company_logo[^}]*\}\}(?:="[^"]*"|=""|"")*/i',
            '{{ !empty($settings[\'company_logo\']) ? url($settings[\'company_logo\']) : \'\' }}"',
            $content
        );

        // 2. Mangled logoToUse / companyLogo tag (e.g. {{ asset(ltrim($logoToUse, " '))="" }}"="")
        $content = preg_replace(
            '/\{\{\s*asset\(ltrim\(\$(?:companyLogo|logoToUse)[^}]+\}\}(?:="[^"]*"|=""|"")*/i',
            '{{ !empty($logoToUse) ? url($logoToUse) : (!empty($companyLogo) ? url($companyLogo) : (!empty($settings[\'company_logo\']) ? url($settings[\'company_logo\']) : \'\')) }}"',
            $content
        );

        // 3. Mangled email_images tag (e.g. {{ url(" email_images="" invoice.png')="" }}"="")
        $content = preg_replace_callback(
            '/\{\{\s*(?:url|asset)\([^}]*?email_images[^}]*?([a-zA-Z0-9_\-\.]+\.(?:png|jpg|jpeg|svg|gif|webp))[^}]*\}\}(?:="[^"]*"|=""|"")*/i',
            function($m) {
                return '{{ url(\'email_images/' . trim($m[1]) . '\') }}"';
            },
            $content
        );

        // 4. Clean up any trailing attribute debris like }}"" or }}"=""
        $content = str_replace(['}}""', '}}"=""', '}}="""', '"\'', '\'"'], ['}}"', '}}"', '}}"', '\'', '\''], $content);
        $content = preg_replace('/(\{\{[^}]+\}\})"+/i', '$1"', $content);
        
        // Ensure any random non-breaking spaces before blade tags are removed
        $content = str_replace('&nbsp;', ' ', $content);

        // Automatically fetch and merge global settings for easy replacements
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
        $settingsData = Setting::whereIn('key', $keys)->pluck('value', 'key')->toArray();
        $platformCompanyName = !empty($settingsData['company_name']) ? $settingsData['company_name'] : 'TidCraft';
        $settingsData['company_name'] = $platformCompanyName;
        
        foreach ($settingsData as $k => $v) {
            // Ensure logo and image URLs are absolute for emails
            if (in_array($k, ['company_logo', 'company_favicon', 'company_short_logo']) && $v && !str_starts_with($v, 'http')) {
                $v = asset($v);
            }
            $settingsData[$k] = $v;
            if (!isset($replacements['{' . $k . '}'])) {
                $replacements['{' . $k . '}'] = $v;
            }
        }

        $platformLogoUrl = !empty($settingsData['company_logo']) 
            ? (str_starts_with($settingsData['company_logo'], 'http') ? $settingsData['company_logo'] : asset($settingsData['company_logo']))
            : asset('storage/settings/lUvNMB4ku94XZPnaGVueDO9rYx3TnakYlcPnoqo6.jpg');
        $settingsData['company_logo'] = $platformLogoUrl;

        // CRITICAL: Enforce that {company_name} ALWAYS represents our platform super admin settings value, NEVER a client company name
        $replacements['{company_name}'] = $platformCompanyName;
        $replacements['{{company_name}}'] = $platformCompanyName;
        $replacements['{platform_name}'] = $platformCompanyName;
        $replacements['{{platform_name}}'] = $platformCompanyName;

        // Logo placeholders
        $replacements['{company_logo}'] = $platformLogoUrl;
        $replacements['{{company_logo}}'] = $platformLogoUrl;
        $replacements['{logo_url}'] = $platformLogoUrl;
        $replacements['{{logo_url}}'] = $platformLogoUrl;

        // Automatically replace {year}
        if (!isset($replacements['{year}'])) {
            $replacements['{year}'] = date('Y');
        }

        // We also want to support raw Blade syntax since the user pasted Blade code.
        // We will pass the replacements as array data to Blade::render.
        // Convert placeholders like '{name}' to just 'name' for the data array
        $bladeData = [
            'settings' => $settingsData,
            'company_name' => $platformCompanyName,
            'platform_name' => $platformCompanyName,
        ];
        
        $stringReplacements = [];
        foreach ($replacements as $key => $value) {
            $cleanKey = trim($key, '{}');
            $bladeData[$cleanKey] = $value;
            
            // Only perform string replacement for scalar values to avoid 'Object to string conversion' errors
            if (is_scalar($value)) {
                $subject = str_replace($key, $value, $subject);
                // Only replace placeholders enclosed in brackets (e.g. {name} or {{name}}) in HTML content
                // to avoid accidentally replacing common words like 'name' in HTML tags or PHP variable names.
                if (str_starts_with($key, '{') && str_ends_with($key, '}')) {
                    $stringReplacements[$key] = $value;
                }
            }
        }

        // Ensure stringReplacements definitely retains platform company name and logo
        $stringReplacements['{company_name}'] = $platformCompanyName;
        $stringReplacements['{{company_name}}'] = $platformCompanyName;
        $stringReplacements['{platform_name}'] = $platformCompanyName;
        $stringReplacements['{{platform_name}}'] = $platformCompanyName;
        $stringReplacements['{company_logo}'] = $platformLogoUrl;
        $stringReplacements['{{company_logo}}'] = $platformLogoUrl;
        $stringReplacements['{logo_url}'] = $platformLogoUrl;
        $stringReplacements['{{logo_url}}'] = $platformLogoUrl;

        // Provide safe defaults for variables commonly expected by email templates
        if (!isset($bladeData['tenant'])) {
            $fallbackTenant = new \stdClass();
            $fallbackClient = new \stdClass();
            $fallbackClient->name = $bladeData['name'] ?? $bladeData['client_name'] ?? 'Client';
            $fallbackClient->email = $bladeData['email'] ?? $bladeData['client_email'] ?? '';
            $fallbackTenant->client = $fallbackClient;
            $fallbackTenant->business_name = $bladeData['business_name'] ?? $bladeData['tenant_name'] ?? 'Tidcraft';
            $fallbackDomain = new \stdClass();
            $fallbackDomain->domain = $bladeData['domain'] ?? 'yourdomain.com';
            $fallbackTenant->domain = $fallbackDomain;
            $fallbackProduct = new \stdClass();
            $fallbackProduct->name = $bladeData['product_name'] ?? 'Application';
            $fallbackTenant->product = $fallbackProduct;
            $bladeData['tenant'] = $fallbackTenant;
        } else {
            if (is_object($bladeData['tenant'])) {
                if (!isset($bladeData['tenant']->product)) {
                    $fallbackProduct = new \stdClass();
                    $fallbackProduct->name = $bladeData['product_name'] ?? 'Application';
                    $bladeData['tenant']->product = $fallbackProduct;
                }
                if (!isset($bladeData['tenant']->client)) {
                    $fallbackClient = new \stdClass();
                    $fallbackClient->name = $bladeData['name'] ?? $bladeData['client_name'] ?? 'Client';
                    $fallbackClient->email = $bladeData['email'] ?? $bladeData['client_email'] ?? '';
                    $bladeData['tenant']->client = $fallbackClient;
                }
                if (!isset($bladeData['tenant']->business_name)) {
                    $bladeData['tenant']->business_name = $bladeData['business_name'] ?? $bladeData['tenant_name'] ?? 'Tidcraft';
                }
            }
        }

        if (!isset($bladeData['payment'])) {
            $fallbackPayment = new class {
                public $id = 1;
                public $order_id = 'ORD-000';
                public $amount = 0.00;
                public $currency = 'INR';
                public $payment_method = 'Online Transfer';
                public function __get($name) {
                    if (in_array($name, ['created_at', 'updated_at', 'create_at', 'update_at'])) {
                        return now();
                    }
                    return null;
                }
            };
            $bladeData['payment'] = $fallbackPayment;
        }

        if (!isset($bladeData['domainUrl'])) {
            $bladeData['domainUrl'] = $bladeData['domain_url'] ?? config('app.url');
        }
        if (!isset($bladeData['adminUrl'])) {
            $bladeData['adminUrl'] = $bladeData['admin_url'] ?? (config('app.url') . '/admin');
        }
        if (!isset($bladeData['adminEmail'])) {
            $bladeData['adminEmail'] = $bladeData['admin_email'] ?? ($bladeData['email'] ?? 'admin@example.com');
        }
        if (!isset($bladeData['adminPassword'])) {
            $bladeData['adminPassword'] = $bladeData['admin_password'] ?? ($bladeData['password'] ?? '********');
        }
        if (!isset($bladeData['clientName'])) {
            $bladeData['clientName'] = $bladeData['client_name'] ?? ($bladeData['name'] ?? 'Client');
        }

        if (!isset($bladeData['inquiry'])) {
            $fallbackInquiry = new \stdClass();
            $fallbackInquiry->id = $bladeData['id'] ?? 1;
            $fallbackInquiry->name = $bladeData['name'] ?? 'User';
            $fallbackInquiry->customer_name = $bladeData['customer_name'] ?? ($bladeData['name'] ?? 'User');
            $fallbackInquiry->email = $bladeData['email'] ?? '';
            $fallbackInquiry->customer_email = $bladeData['customer_email'] ?? ($bladeData['email'] ?? '');
            $fallbackInquiry->phone = $bladeData['phone'] ?? '';
            $fallbackInquiry->customer_phone = $bladeData['customer_phone'] ?? ($bladeData['phone'] ?? '');
            $fallbackInquiry->subject = $bladeData['subject'] ?? 'Inquiry';
            $fallbackInquiry->message = $bladeData['message'] ?? '';
            $fallbackInquiry->created_at = now();
            $bladeData['inquiry'] = $fallbackInquiry;
        } else {
            if (is_object($bladeData['inquiry'])) {
                if (!isset($bladeData['inquiry']->id)) {
                    $bladeData['inquiry']->id = $bladeData['id'] ?? 1;
                }
                if (!isset($bladeData['inquiry']->customer_name)) {
                    $bladeData['inquiry']->customer_name = $bladeData['inquiry']->name ?? ($bladeData['name'] ?? 'User');
                }
                if (!isset($bladeData['inquiry']->customer_email)) {
                    $bladeData['inquiry']->customer_email = $bladeData['inquiry']->email ?? ($bladeData['email'] ?? '');
                }
                if (!isset($bladeData['inquiry']->customer_phone)) {
                    $bladeData['inquiry']->customer_phone = $bladeData['inquiry']->phone ?? '';
                }
            }
        }

        if (!isset($bladeData['messageStr'])) {
            $bladeData['messageStr'] = $bladeData['message'] ?? 'Your subscription will expire soon.';
        }
        if (!isset($bladeData['companyName'])) {
            $bladeData['companyName'] = $platformCompanyName;
        }
        if (!isset($bladeData['companyPhone'])) {
            $bladeData['companyPhone'] = $settingsData['company_phone'] ?? '';
        }
        if (!isset($bladeData['companyEmail'])) {
            $bladeData['companyEmail'] = $settingsData['company_email'] ?? '';
        }

        if (!isset($bladeData['title'])) {
            $bladeData['title'] = $bladeData['subject'] ?? $subject ?? 'Notification';
        }

        // Dynamically provide frontend URLs
        $defaultFrontendUrl = \App\Helpers\UrlHelper::getFrontendUrl();
        $defaultLoginUrl = \App\Helpers\UrlHelper::getLoginUrl();
        $defaultContactUrl = \App\Helpers\UrlHelper::getContactUrl();

        if (!isset($bladeData['frontend_url'])) {
            $bladeData['frontend_url'] = $defaultFrontendUrl;
        }
        if (!isset($bladeData['login_url'])) {
            $bladeData['login_url'] = $defaultLoginUrl;
        }
        if (!isset($bladeData['contact_url'])) {
            $bladeData['contact_url'] = $defaultContactUrl;
        }

        foreach ([
            '{frontend_url}' => $defaultFrontendUrl,
            '{{frontend_url}}' => $defaultFrontendUrl,
            '{login_url}' => $defaultLoginUrl,
            '{{login_url}}' => $defaultLoginUrl,
            '{contact_url}' => $defaultContactUrl,
            '{{contact_url}}' => $defaultContactUrl,
        ] as $k => $v) {
            if (!isset($stringReplacements[$k])) {
                $stringReplacements[$k] = $v;
            }
        }

        try {
            // First run standard string replacements in case they used {name}
            $content = str_replace(array_keys($stringReplacements), array_values($stringReplacements), $content);
            
            // Guarantee that any <img> tag with company_logo or settings logo in src gets the absolute platform logo URL directly
            $content = preg_replace(
                '/<img([^>]*?)src=["\'](?:\{\{|\%7B\%7B)[^"\']*(?:company_logo|lUvNMB4ku94XZPnaGVueDO9rYx3TnakYlcPnoqo6)[^"\']*(?:\}\}|\%7D\%7D)["\']([^>]*?)>/i',
                '<img$1src="' . $platformLogoUrl . '"$2>',
                $content
            );

            // Sanitize any broken /client/login links to /login per requirement
            $content = str_ireplace('/client/login', '/login', $content);
            
            // Inject sensible responsive styles to images without overriding existing explicit dimensions
            $content = preg_replace_callback('/<img\s+((?:[^>"\']|"[^"]*"|\'[^\']*\')+)>/i', function($matches) {
                $imgTag = $matches[0];
                $attrs = $matches[1];

                $hasExplicitWidth = stripos($attrs, 'width:') !== false || stripos($attrs, 'max-width:') !== false || preg_match('/\bwidth=["\']?\d+/i', $attrs);
                $hasExplicitHeight = stripos($attrs, 'height:') !== false || stripos($attrs, 'max-height:') !== false || preg_match('/\bheight=["\']?\d+/i', $attrs);

                $isLogo = stripos($attrs, 'logo') !== false 
                       || stripos($attrs, 'company') !== false 
                       || stripos($attrs, 'brand') !== false;

                if ($isLogo) {
                    // Logos should not expand to 100% width or height: auto
                    if (stripos($attrs, 'style=') === false) {
                        return rtrim($imgTag, '>') . ' style="max-height: 42px; max-width: 180px; width: auto; height: auto; object-fit: contain;">';
                    }
                    return $imgTag;
                }

                // Normal content images (hero illustrations, banners)
                if (stripos($attrs, 'style=') !== false) {
                    if (!$hasExplicitWidth) {
                        return preg_replace('/style=([\'"])/i', 'style=$1max-width: 100%; height: auto; ', $imgTag);
                    }
                    return $imgTag;
                } else {
                    return rtrim($imgTag, '>') . ' style="max-width: 100%; height: auto;">';
                }
            }, $content);

            // Then run Blade compilation
            $compiledContent = Blade::render($content, $bladeData);
        } catch (\Throwable $e) {
            // Fallback if Blade compilation fails due to bad syntax in WYSIWYG
            \Illuminate\Support\Facades\Log::error('Blade render failed in DynamicEmail: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Failing Blade Content: ' . $content);
            $compiledContent = "<div style='background: #ffebe8; border: 1px solid #cc0000; padding: 15px; margin-bottom: 20px; color: #cc0000; font-family: monospace;'>
                <strong>Blade Syntax Error Detected!</strong><br><br>
                <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>
                Please check your email template for typos like using '=' instead of ',' inside a function, or missing closing brackets.
                </div>" . $content;
        }

        $this->dynamicSubject = $subject;
        $this->dynamicContent = $compiledContent;
        $this->settingsData = $settingsData;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        // Detect if the content already has a full HTML structure or layout
        $isFullHtml = stripos($this->dynamicContent, '<html') !== false 
                   || stripos($this->dynamicContent, '<body') !== false 
                   || stripos($this->dynamicContent, '<table') !== false // Email templates almost always use layout tables
                   || stripos($this->dynamicContent, '<style') !== false 
                   || stripos($this->dynamicContent, 'class="email-container"') !== false;

        $viewName = $isFullHtml ? 'emails.layouts.raw' : 'emails.layouts.dynamic';

        $html = view($viewName, [
            'dynamicContent' => $this->dynamicContent,
            'settings' => $this->settingsData,
        ])->render();

        // Convert any <style> blocks to inline CSS automatically for perfect email rendering across all clients
        try {
            if (class_exists(\TijsVerkoyen\CssToInlineStyles\CssToInlineStyles::class)) {
                $inliner = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles();
                $html = $inliner->convert($html);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('CSS Inlining failed: ' . $e->getMessage());
        }

        // Failsafe: if DOM parser encoded any Blade tag or left uncompiled logo in an img tag, fix it to valid URL
        $platformLogo = !empty($this->settingsData['company_logo']) ? $this->settingsData['company_logo'] : asset('storage/settings/lUvNMB4ku94XZPnaGVueDO9rYx3TnakYlcPnoqo6.jpg');
        $html = preg_replace_callback('/<img([^>]*?)src=["\'](?:%7B%7B|\{\{)(.*?)(?:%7D%7D|\}\})["\']([^>]*?)>/i', function($m) use ($platformLogo) {
            $inner = urldecode($m[2]);
            if (stripos($inner, 'logo') !== false || stripos($inner, 'lUvNMB4ku94XZPnaGVueDO9rYx3TnakYlcPnoqo6') !== false) {
                return '<img' . $m[1] . 'src="' . $platformLogo . '"' . $m[3] . '>';
            }
            return '<img' . $m[1] . 'src="' . $platformLogo . '"' . $m[3] . '>';
        }, $html);

        $mail = $this->subject($this->dynamicSubject)
                     ->html($html);

        if (!empty($this->emailAttachments)) {
            foreach ($this->emailAttachments as $att) {
                if (isset($att['data']) && isset($att['name'])) {
                    $mail->attachData(
                        $att['data'],
                        $att['name'],
                        ['mime' => $att['mime'] ?? 'application/pdf']
                    );
                } elseif (isset($att['path'])) {
                    $mail->attach(
                        $att['path'],
                        [
                            'as' => $att['name'] ?? basename($att['path']),
                            'mime' => $att['mime'] ?? 'application/pdf'
                        ]
                    );
                }
            }
        }

        return $mail;
    }
}
