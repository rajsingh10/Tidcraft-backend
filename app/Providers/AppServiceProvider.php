<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (!class_exists(\Razorpay\Api\Api::class) && file_exists(base_path('vendor/razorpay/razorpay/Razorpay.php'))) {
            require_once base_path('vendor/razorpay/razorpay/Razorpay.php');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            if (Schema::hasTable('settings')) {
                $mailSettings = Setting::whereIn('key', [
                    'mail_mailer',
                    'mail_host',
                    'mail_port',
                    'mail_username',
                    'mail_password',
                    'mail_encryption',
                    'mail_from_address',
                    'mail_from_name',
                ])->pluck('value', 'key');

                if ($mailSettings->count() > 0) {
                    if ($mailSettings->has('mail_mailer') && !empty($mailSettings['mail_mailer'])) {
                        Config::set('mail.default', $mailSettings['mail_mailer']);
                    } elseif ($mailSettings->has('mail_host') && !empty($mailSettings['mail_host'])) {
                        Config::set('mail.default', 'smtp');
                    }
                    if ($mailSettings->has('mail_host')) Config::set('mail.mailers.smtp.host', $mailSettings['mail_host']);
                    if ($mailSettings->has('mail_port')) Config::set('mail.mailers.smtp.port', $mailSettings['mail_port']);
                    if ($mailSettings->has('mail_encryption')) Config::set('mail.mailers.smtp.encryption', $mailSettings['mail_encryption']);
                    if ($mailSettings->has('mail_username')) Config::set('mail.mailers.smtp.username', $mailSettings['mail_username']);
                    if ($mailSettings->has('mail_password')) Config::set('mail.mailers.smtp.password', $mailSettings['mail_password']);
                    if ($mailSettings->has('mail_from_address')) Config::set('mail.from.address', $mailSettings['mail_from_address']);
                    if ($mailSettings->has('mail_from_name')) Config::set('mail.from.name', $mailSettings['mail_from_name']);
                }
            }
        } catch (\Exception $e) {
            // Silently ignore if DB or table isn't set up yet
        }
    }
}
