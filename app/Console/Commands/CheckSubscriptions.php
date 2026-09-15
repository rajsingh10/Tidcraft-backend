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
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->get();

        foreach ($activeSubscriptions as $subscription) {
            $endDate = \Carbon\Carbon::parse($subscription->end_date)->startOfDay();
            $tenant = $subscription->tenant;

            if (!$tenant) continue;

            $diffInDays = $today->diffInDays($endDate, false);

            if ($diffInDays <= 5 && $diffInDays > 0) {
                $this->notifyClient(
                    $tenant, 
                    $diffInDays == 1 ? 'Urgent: Subscription Expiring Tomorrow' : 'Subscription Expiring Soon', 
                    "Your subscription will expire in {$diffInDays} day(s). Please renew to avoid service interruption."
                );
            } elseif ($diffInDays <= 0) {
                // Subscription has expired
                $subscription->update(['status' => 'expired']);
                $tenant->update(['status' => 'expired']);
                
                \App\Services\TenantProvisionService::blockTenant($tenant);

                $this->notifyClient($tenant, 'Subscription Expired', 'Your subscription has expired and your application has been blocked. Please renew to restore access.');
                
                $this->info("Tenant {$tenant->tenant_key} has been expired and blocked.");
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
