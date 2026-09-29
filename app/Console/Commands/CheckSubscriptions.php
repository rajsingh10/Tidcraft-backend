<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check active subscriptions for expiry and process warnings/blocking.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = \Carbon\Carbon::now()->startOfDay();

        $activeSubscriptions = \App\Models\Subscription::with('tenant')
            ->whereIn('status', ['active', 'past_due'])
            ->whereNotNull('end_date')
            ->get();

        foreach ($activeSubscriptions as $subscription) {
            $endDate = \Carbon\Carbon::parse($subscription->end_date)->startOfDay();
            $tenant = $subscription->tenant;

            if (!$tenant) continue;

            $diffInDays = $today->diffInDays($endDate, false);
            
            // Dynamic grace period (default 45 days ~ 1.5 months)
            $gracePeriodDays = (int) (\App\Models\Setting::where('key', 'suspension_grace_period_days')->value('value') ?? 45);

            if ($diffInDays <= 5 && $diffInDays > 0) {
                $this->notifyClient(
                    $tenant, 
                    $diffInDays == 1 ? 'Urgent: Subscription Expiring Tomorrow' : 'Subscription Expiring Soon', 
                    "Your subscription will expire in {$diffInDays} day(s). Please renew to avoid service interruption."
                );
            } elseif ($diffInDays <= 0 && $diffInDays > -$gracePeriodDays) {
                // Subscription is expired but within grace period
                if ($subscription->status !== 'past_due') {
                    $subscription->update(['status' => 'past_due']);
                    $tenant->update(['status' => 'past_due']);
                    
                    $this->notifyClient($tenant, 'Subscription Past Due', "Your subscription has expired. You have a grace period of {$gracePeriodDays} days to clear your dues before your application is suspended.");
                    
                    $this->info("Tenant {$tenant->tenant_key} is past due (grace period).");
                }
            } elseif ($diffInDays <= -$gracePeriodDays) {
                // Grace period has ended, block tenant
                if ($subscription->status !== 'expired') {
                    $subscription->update(['status' => 'expired']);
                    $tenant->update(['status' => 'suspended']);
                    
                    \App\Services\TenantProvisionService::blockTenant($tenant);

                    $this->notifyClient($tenant, 'Account Suspended', 'Your subscription is ' . abs($diffInDays) . ' days past due. Your application has been suspended. Please renew to restore access.');
                    
                    $this->info("Tenant {$tenant->tenant_key} has been suspended and blocked.");
                }
            }
        }
    }

    private function notifyClient(\App\Models\Tenant $tenant, $title, $message)
    {
        \App\Models\AdminNotification::create([
            'type' => 'subscription_warning',
            'title' => $title,
            'message' => $message,
            'related_id' => $tenant->id,
            'client_name' => $tenant->client ? $tenant->client->name : 'Client',
            'is_read' => false,
        ]);

        $adminEmail = $tenant->primary_contact_email ?? ($tenant->client ? $tenant->client->email : null);
        if ($adminEmail) {
            try {
                \Illuminate\Support\Facades\Mail::to($adminEmail)->send(new \App\Mail\SubscriptionExpiryEmail($tenant, $title, $message));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send subscription expiry email: ' . $e->getMessage());
            }
        }
    }
}
