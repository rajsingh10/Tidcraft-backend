<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\Domain;

class TenantPlanStatusController extends Controller
{
    /**
     * Get the live plan, quotas, and subscription status for a tenant by domain or key.
     * Accessible by client apps and admin panels.
     */
    public function show(Request $request)
    {
        $tenant = $this->findTenantFromRequest($request);

        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant not found for the given domain or key.'
            ], 404);
        }

        $plan = $tenant->plan;
        $subscription = $tenant->subscriptions->sortByDesc('id')->first();
        $clientAppUrl = config('app.url', 'https://tidcraft.com');

        $maxOrders = $tenant->getMaxOrders();
        $maxUsers = $tenant->getMaxUsers();
        $storageGb = $tenant->getStorageLimitGb();

        $canPerformAction = $tenant->hasActiveSubscription() && $tenant->status === 'active';

        return response()->json([
            'status' => 'success',
            'data' => [
                'tenant' => [
                    'id' => $tenant->id,
                    'uuid' => $tenant->uuid,
                    'name' => $tenant->name,
                    'business_name' => $tenant->business_name,
                    'tenant_key' => $tenant->tenant_key,
                    'status' => $tenant->status,
                    'primary_domain' => $tenant->domains->first()?->domain ?? null,
                ],
                'plan' => $plan ? [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'monthly_price' => $plan->monthly_price,
                    'annual_price' => $plan->annual_price,
                    'max_orders' => $maxOrders,
                    'max_users' => $maxUsers,
                    'storage_gb' => $storageGb,
                    'additional_order_price' => $plan->additional_order_price,
                    'store_configuration' => $plan->store_configuration,
                    'has_hybrid_customer_app' => (bool) $plan->has_hybrid_customer_app,
                    'has_hybrid_customer_merchant_app' => (bool) $plan->has_hybrid_customer_merchant_app,
                    'has_unlimited_users_listings' => (bool) $plan->has_unlimited_users_listings,
                    'has_white_labeled_solution' => (bool) $plan->has_white_labeled_solution,
                    'has_white_labeled_dashboard' => (bool) $plan->has_white_labeled_dashboard,
                    'features' => is_string($plan->features) ? json_decode($plan->features, true) : ($plan->features ?? []),
                ] : null,
                'subscription' => $subscription ? [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'is_active' => $tenant->hasActiveSubscription(),
                    'start_date' => $subscription->start_date,
                    'end_date' => $subscription->end_date,
                    'duration_days' => $tenant->duration_days,
                    'remaining_days' => $tenant->remaining_days,
                ] : null,
                'quotas' => [
                    'max_orders' => $maxOrders,
                    'max_users' => $maxUsers,
                    'storage_gb' => $storageGb,
                    'can_place_order' => $canPerformAction,
                    'can_create_user' => $canPerformAction,
                    'is_subscription_active' => $tenant->hasActiveSubscription(),
                ],
                'billing' => [
                    'upgrade_url' => rtrim($clientAppUrl, '/') . '/client/purchases/' . $tenant->uuid . '/upgrade',
                    'renew_url' => rtrim($clientAppUrl, '/') . '/client/purchases/' . $tenant->uuid . '/renew',
                ]
            ]
        ]);
    }

    /**
     * Check if a specific action (order placement, user creation, store creation)
     * is permitted under the tenant's current plan and quota.
     * Can be called by Customer App, Restaurant App, Driver App, or Admin Panels.
     */
    public function checkQuota(Request $request)
    {
        $tenant = $this->findTenantFromRequest($request);

        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'allowed' => false,
                'reason' => 'TENANT_NOT_FOUND',
                'message' => 'Tenant not found.'
            ], 404);
        }

        // 1. Check Tenant Status
        if ($tenant->status === 'suspended' || $tenant->status === 'inactive') {
            return response()->json([
                'status' => 'success',
                'allowed' => false,
                'reason' => 'TENANT_SUSPENDED',
                'message' => 'Tenant account is currently suspended or inactive. Please contact support.',
                'data' => [
                    'tenant_id' => $tenant->id,
                    'status' => $tenant->status,
                ]
            ]);
        }

        // 2. Check Subscription
        $hasActiveSub = $tenant->hasActiveSubscription();
        if (!$hasActiveSub) {
            return response()->json([
                'status' => 'success',
                'allowed' => false,
                'reason' => 'SUBSCRIPTION_EXPIRED',
                'message' => 'Subscription has expired or is inactive. Please renew to continue.',
                'data' => [
                    'tenant_id' => $tenant->id,
                    'status' => $tenant->status,
                    'is_subscription_active' => false,
                    'remaining_days' => 0
                ]
            ]);
        }

        $plan = $tenant->plan;
        $maxOrders = $tenant->getMaxOrders();
        $maxUsers = $tenant->getMaxUsers();
        $storageGb = $tenant->getStorageLimitGb();
        $storeConfig = $plan ? $plan->store_configuration : 'single';

        $action = $request->query('action', 'general'); // 'order', 'user', 'store', 'general'
        $currentCount = $request->has('current_count') ? (int) $request->query('current_count') : null;

        $allowed = true;
        $reason = 'OK';
        $message = 'Action permitted under current plan.';

        if ($action === 'order') {
            if ($maxOrders > 0 && $currentCount !== null && $currentCount >= $maxOrders) {
                $allowed = false;
                $reason = 'ORDER_LIMIT_REACHED';
                $message = "Order limit reached ({$currentCount}/{$maxOrders}). Please upgrade your plan or contact the store administrator.";
            }
        } elseif ($action === 'user') {
            if ($maxUsers > 0 && $currentCount !== null && $currentCount >= $maxUsers) {
                $allowed = false;
                $reason = 'USER_LIMIT_REACHED';
                $message = "User limit reached ({$currentCount}/{$maxUsers}). Please upgrade your plan to add more users.";
            }
        } elseif ($action === 'store') {
            if (in_array($storeConfig, ['single', 'single_store']) && $currentCount !== null && $currentCount >= 1) {
                $allowed = false;
                $reason = 'STORE_LIMIT_REACHED';
                $message = "Single Store plan allows only 1 restaurant/store. Upgrade to Multi Store to create additional locations.";
            }
        }

        return response()->json([
            'status' => 'success',
            'allowed' => $allowed,
            'reason' => $reason,
            'message' => $message,
            'data' => [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan?->id,
                'plan_name' => $plan?->name ?? 'Default',
                'action' => $action,
                'current_count' => $currentCount,
                'max_orders' => $maxOrders,
                'max_users' => $maxUsers,
                'storage_gb' => $storageGb,
                'store_configuration' => $storeConfig,
                'is_subscription_active' => true,
                'remaining_days' => $tenant->remaining_days,
            ]
        ]);
    }

    /**
     * Helper to resolve tenant from domain, tenant_key, or uuid
     */
    protected function findTenantFromRequest(Request $request): ?Tenant
    {
        $domain = $request->query('domain') ?? $request->header('X-Tenant-Domain');
        $tenantKey = $request->query('tenant_key');
        $uuid = $request->query('uuid');

        $tenant = null;

        if ($uuid) {
            $tenant = Tenant::with(['plan', 'subscriptions', 'domains', 'product'])->where('uuid', $uuid)->first();
        }

        if (!$tenant && $tenantKey) {
            $tenant = Tenant::with(['plan', 'subscriptions', 'domains', 'product'])->where('tenant_key', $tenantKey)->first();
        }

        if (!$tenant && $domain) {
            $cleanDomain = preg_replace('#^https?://#', '', $domain);
            $cleanDomain = explode(':', $cleanDomain)[0];
            $cleanDomain = trim($cleanDomain, '/');

            $domainModel = Domain::where('domain', $cleanDomain)->first();
            if ($domainModel) {
                $tenant = Tenant::with(['plan', 'subscriptions', 'domains', 'product'])->find($domainModel->tenant_id);
            }

            if (!$tenant && str_contains($cleanDomain, '.')) {
                $subdomain = explode('.', $cleanDomain)[0];
                $tenant = Tenant::with(['plan', 'subscriptions', 'domains', 'product'])
                    ->where('tenant_key', $subdomain)
                    ->orWhere('name', 'LIKE', $subdomain . '%')
                    ->first();
            }
        }

        return $tenant;
    }
}
