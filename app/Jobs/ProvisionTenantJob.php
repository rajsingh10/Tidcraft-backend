<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\TenantProvisionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProvisionTenantJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 300;

    public $backoff = [15, 60, 120];

    public function uniqueId(): string
    {
        return 'provision-tenant-' . $this->tenant->id;
    }

    protected Tenant $tenant;

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public function handle(): void
    {
        $tenant = Tenant::with(['database', 'firebaseProject', 'domains', 'subscriptions'])->find($this->tenant->id);

        if (!$tenant) {
            return;
        }

        if ($tenant->status === 'active' && $tenant->database?->status === 'ready') {
            return;
        }

        TenantProvisionService::provision($tenant);
    }
}
