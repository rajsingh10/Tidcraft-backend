<?php

namespace App\Helpers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailHelper
{
    /**
     * List of invalid or placeholder email addresses that must never receive emails.
     */
    protected static array $blacklistedEmails = [
        'admin@tidcraft.com',
        'admin@example.com',
        'superadmin@example.com',
        'user@example.com',
    ];

    /**
     * Get a list of verified, valid admin notification email addresses.
     * Includes configured admin_cc_emails, company_email, and SuperAdmin user emails,
     * while filtering out blacklisted and invalid mailboxes.
     *
     * @return array<string>
     */
    public static function getAdminNotificationEmails(): array
    {
        $emails = [];

        // 1. Configured CC emails in SMTP / general settings
        try {
            $ccSetting = Setting::where('key', 'admin_cc_emails')->value('value');
            if ($ccSetting) {
                $decoded = json_decode($ccSetting, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    foreach ($decoded as $email) {
                        if (is_string($email)) {
                            $emails[] = trim($email);
                        }
                    }
                } elseif (is_string($ccSetting)) {
                    foreach (explode(',', $ccSetting) as $email) {
                        $emails[] = trim($email);
                    }
                }
            }

            // 2. Company contact email setting
            $companyEmail = Setting::where('key', 'company_email')->value('value');
            if ($companyEmail) {
                $emails[] = trim($companyEmail);
            }

            // 3. SuperAdmin user emails from database
            $superAdmins = User::role('SuperAdmin')->pluck('email')->toArray();
            foreach ($superAdmins as $superAdminEmail) {
                if ($superAdminEmail) {
                    $emails[] = trim($superAdminEmail);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error fetching admin notification emails: ' . $e->getMessage());
        }

        // Filter and clean emails
        $validEmails = [];
        foreach ($emails as $email) {
            $lower = strtolower(trim((string)$email));
            if (empty($lower)) {
                continue;
            }
            if (!filter_var($lower, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            if (in_array($lower, self::$blacklistedEmails, true)) {
                continue;
            }
            $validEmails[] = $lower;
        }

        $validEmails = array_values(array_unique($validEmails));

        // 4. Fallback if no valid emails found: use mail.from.address (verified sending mailbox)
        if (empty($validEmails)) {
            $fromAddress = strtolower(trim((string) (config('mail.from.address') ?? Setting::where('key', 'mail_from_address')->value('value'))));
            if (!empty($fromAddress) && filter_var($fromAddress, FILTER_VALIDATE_EMAIL) && !in_array($fromAddress, self::$blacklistedEmails, true)) {
                $validEmails[] = $fromAddress;
            }
        }

        return $validEmails;
    }

    /**
     * Send mailable to all verified admin notification mailboxes.
     * Logs any individual failure cleanly without breaking the flow.
     *
     * @param Mailable $mailable
     * @return int Number of successfully dispatched emails
     */
    public static function sendToAdmins(Mailable $mailable): int
    {
        $recipients = self::getAdminNotificationEmails();
        if (empty($recipients)) {
            Log::warning('No verified admin notification mailboxes configured. Email not sent: ' . get_class($mailable));
            return 0;
        }

        $sentCount = 0;
        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient)->send($mailable);
                $sentCount++;
            } catch (\Exception $e) {
                Log::error("Failed to send admin notification email to [{$recipient}]: " . $e->getMessage());
            }
        }

        return $sentCount;
    }
}
