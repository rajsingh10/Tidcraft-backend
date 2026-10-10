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
                    'onboarding_fee' => $plan->onboarding_fee,
                    'max_orders' => $maxOrders,
                    'max_locations' => $plan->max_locations,
                    'max_bookings' => $plan->max_bookings,
                    'max_users' => $maxUsers,
                    'storage_gb' => $storageGb,
                    'additional_order_price' => $plan->additional_order_price,
                    'additional_booking_price' => $plan->additional_booking_price,
                    'store_configuration' => $plan->store_configuration,
                    'has_hybrid_customer_app' => (bool) $plan->has_hybrid_customer_app,
                    'has_hybrid_customer_merchant_app' => (bool) $plan->has_hybrid_customer_merchant_app,
                    'has_unlimited_users_listings' => (bool) $plan->has_unlimited_users_listings,
                    'has_white_labeled_solution' => (bool) $plan->has_white_labeled_solution,
                    'has_white_labeled_dashboard' => (bool) $plan->has_white_labeled_dashboard,
                    'has_customer_app' => (bool) $plan->has_customer_app,
                    'has_merchant_app' => (bool) $plan->has_merchant_app,
                    'has_rider_app' => (bool) $plan->has_rider_app,
                    'has_white_labeled_app' => is_string($plan->has_white_labeled_app) ? json_decode($plan->has_white_labeled_app, true) : ($plan->has_white_labeled_app ?? []),
                    'has_watchman_app' => (bool) $plan->has_watchman_app,
                    'has_owner_app' => (bool) $plan->has_owner_app,
                    'features' => is_string($plan->features) ? json_decode($plan->features, true) : ($plan->features ?? []),
                    'feature_flags' => $this->resolveFeatureFlags($plan),
                ] : null,
                'subscription' => $subscription ? [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'is_active' => $tenant->hasActiveSubscription(),
                    'start_date' => $subscription->start_date,
                    'end_date' => $subscription->end_date,
                    'duration_days' => $tenant->duration_days,
                    'remaining_days' => $tenant->remaining_days,
                    'expiry_date' => $tenant->expiry_date,
                ] : null,
                'quotas' => [
                    'max_orders' => $maxOrders,
                    'max_users' => $maxUsers,
                    'max_locations' => $plan?->max_locations,
                    'max_bookings' => $plan?->max_bookings,
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
     * Unified app config endpoint for Customer App, Owner App, and Watchman App.
     */
    public function appConfig(Request $request)
    {
        $tenant = $this->findTenantFromRequest($request);

        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant not found for the given domain, key, or uuid.'
            ], 404);
        }

        $plan = $tenant->plan;
        $isActive = $tenant->hasActiveSubscription() && $tenant->status === 'active';
        $appType = strtolower($request->query('app_type', 'all')); // 'customer', 'owner', 'watchman', 'all'
        $clientAppUrl = config('app.url', 'https://tidcraft.com');
        $upgradeUrl = rtrim($clientAppUrl, '/') . '/client/purchases/' . $tenant->uuid . '/upgrade';

        $featureFlags = $this->resolveFeatureFlags($plan);
        $maxLocations = $plan && isset($plan->max_locations) && (int)$plan->max_locations > 0 ? (int)$plan->max_locations : -1;
        $maxBookings = $plan && isset($plan->max_bookings_monthly) && (int)$plan->max_bookings_monthly > 0 ? (int)$plan->max_bookings_monthly : ($plan && (int)($plan->max_bookings ?? 0) > 0 ? (int)$plan->max_bookings : -1);

        $response = [
            'status' => 'success',
            'app_type' => $appType,
            'tenant' => [
                'id' => $tenant->id,
                'uuid' => $tenant->uuid,
                'name' => $tenant->name,
                'business_name' => $tenant->business_name,
                'tenant_key' => $tenant->tenant_key,
                'status' => $tenant->status,
                'is_subscription_active' => $isActive,
                'remaining_days' => $tenant->remaining_days,
                'expiry_date' => $tenant->expiry_date,
            ],
            'plan' => [
                'id' => $plan?->id,
                'name' => $plan?->name ?? 'Default',
                'max_locations' => $maxLocations,
                'max_bookings' => $maxBookings,
                'feature_flags' => $featureFlags,
            ],
            'billing' => [
                'upgrade_url' => $upgradeUrl,
            ]
        ];

        if ($appType === 'watchman') {
            $allowed = $isActive && ($featureFlags['watchman_management'] || (bool)($plan?->has_watchman_app ?? false));
            $response['access'] = [
                'allowed' => $allowed,
                'reason' => $allowed ? 'OK' : (!$isActive ? 'SUBSCRIPTION_INACTIVE' : 'WATCHMAN_APP_NOT_INCLUDED'),
                'message' => $allowed ? 'Access granted.' : (!$isActive ? 'Subscription is inactive. Please contact your organization administrator.' : 'Watchman app access is not included in your organization\'s current plan. Please upgrade to the Growth plan.'),
                'upgrade_url' => $upgradeUrl,
            ];
        } elseif ($appType === 'owner') {
            $response['owner_config'] = [
                'max_locations' => $maxLocations,
                'wallet_enabled' => $featureFlags['owner_wallet'],
                'watchman_management_enabled' => $featureFlags['watchman_management'],
                'ev_charging_enabled' => $featureFlags['ev_charging'],
                'owner_wise_commission_enabled' => $featureFlags['owner_wise_commission'],
                'is_white_labeled' => $featureFlags['white_label_owner_app'],
            ];
        } elseif ($appType === 'customer') {
            $response['customer_config'] = [
                'can_book' => $isActive,
                'ev_charging_filter' => $featureFlags['ev_charging'],
                'is_white_labeled' => true,
            ];
        }

        return response()->json($response);
    }

    /**
     * Check if a specific action (order placement, user creation, store creation, location creation, watchman, booking)
     * is permitted under the tenant's current plan and quota.
     * Can be called by Customer App, Owner App, Watchman App, Restaurant App, Driver App, or Admin Panels.
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

        $action = $request->query('action', 'general'); // 'location', 'watchman', 'booking', 'order', 'user', 'store', 'general'
        $currentCount = $request->has('current_count') ? (int) $request->query('current_count') : null;

        $allowed = true;
        $reason = 'OK';
        $message = 'Action permitted under current plan.';

        if ($action === 'location') {
            $maxLocations = $plan && isset($plan->max_locations) && (int)$plan->max_locations > 0 ? (int)$plan->max_locations : -1;
            if ($maxLocations > 0 && $currentCount !== null && $currentCount >= $maxLocations) {
                $allowed = false;
                $reason = 'LOCATION_LIMIT_REACHED';
                $message = "Parking location limit reached ({$currentCount}/{$maxLocations}). Please upgrade your plan to register additional parking locations.";
            }
        } elseif ($action === 'watchman') {
            $featureFlags = $this->resolveFeatureFlags($plan);
            if (!$featureFlags['watchman_management'] && empty($plan?->has_watchman_app)) {
                $allowed = false;
                $reason = 'WATCHMAN_FEATURE_NOT_INCLUDED';
                $message = "Watchman management is not included in your current plan. Please upgrade to the Growth plan.";
            }
        } elseif ($action === 'booking') {
            $maxBookings = $plan && isset($plan->max_bookings_monthly) && (int)$plan->max_bookings_monthly > 0 ? (int)$plan->max_bookings_monthly : ($plan && (int)($plan->max_bookings ?? 0) > 0 ? (int)$plan->max_bookings : -1);
            $additionalBookingPrice = (float) ($plan?->additional_booking_price ?? 0);
            if ($maxBookings > 0 && $currentCount !== null && $currentCount >= $maxBookings) {
                if ($additionalBookingPrice > 0) {
                    $allowed = true;
                    $reason = 'BOOKING_OVERAGE_ACTIVE';
                    $message = "Included booking limit reached ({$currentCount}/{$maxBookings}). Additional bookings are permitted and billed at ₹{$additionalBookingPrice}/booking.";
                } else {
                    $allowed = false;
                    $reason = 'BOOKING_LIMIT_REACHED';
                    $message = "Monthly booking limit reached ({$currentCount}/{$maxBookings}). Please upgrade your plan or contact support.";
                }
            }
        } elseif ($action === 'order') {
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
                'max_locations' => $plan?->max_locations,
                'max_bookings' => $plan?->max_bookings,
                'max_orders' => $maxOrders,
                'max_users' => $maxUsers,
                'storage_gb' => $storageGb,
                'store_configuration' => $storeConfig,
                'is_subscription_active' => true,
                'remaining_days' => $tenant->remaining_days,
                'expiry_date' => $tenant->expiry_date,
            ]
        ]);
    }

    /**
     * Report live order count from FoodApp / Firestore, calculate additional order overage,
     * and automatically sync or generate post-paid overage invoice.
     */
    public function syncOrderUsage(Request $request)
    {
        $tenant = $this->findTenantFromRequest($request);

        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant not found.'
            ], 404);
        }

        $orderCount = (int) $request->input('order_count', 0);
        if ($orderCount >= 0 && $orderCount !== (int) ($tenant->current_orders_count ?? 0)) {
            $tenant->update(['current_orders_count' => $orderCount]);
        }
        $overageData = \App\Services\OverageBillingService::calculateOverage($tenant, $orderCount);

        $pendingInvoice = null;
        if ($overageData['is_exceeded'] && $overageData['overage_orders'] > 0 && $overageData['rate_per_order'] > 0) {
            $payment = \App\Services\OverageBillingService::syncOverageInvoice($tenant, $orderCount);
            if ($payment) {
                $pendingInvoice = [
                    'id' => $payment->id,
                    'invoice_number' => $payment->invoice_number,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency ?? 'INR',
                    'status' => $payment->status,
                    'status_label' => 'Pending (Pay Later)',
                    'type' => $payment->type,
                    'metadata' => $payment->metadata,
                    'payment_link' => $payment->metadata['payment_link'] ?? null,
                    'created_at' => $payment->created_at?->format('M d, Y'),
                ];
            }
        }

        $clientAppUrl = config('app.url', 'https://tidcraft.com');
        $upgradeUrl = rtrim($clientAppUrl, '/') . '/client/purchases/' . $tenant->uuid . '/upgrade';

        return response()->json([
            'status' => 'success',
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'uuid' => $tenant->uuid,
            ],
            'overage' => $overageData,
            'pending_invoice' => $pendingInvoice,
            'upgrade_url' => $upgradeUrl,
        ]);
    }

    /**
     * Report live booking count from ParkMeApp / Firestore, calculate additional bookings overage,
     * and automatically sync or generate post-paid overage invoice.
     */
    public function syncBookingUsage(Request $request)
    {
        $tenant = $this->findTenantFromRequest($request);

        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant not found.'
            ], 404);
        }

        $bookingCount = (int) ($request->input('booking_count') ?? $request->input('order_count') ?? 0);
        if ($bookingCount >= 0 && $bookingCount !== (int) ($tenant->current_bookings_count ?? 0)) {
            $tenant->update(['current_bookings_count' => $bookingCount]);
        }
        $overageData = \App\Services\OverageBillingService::calculateBookingOverage($tenant, $bookingCount);

        $pendingInvoice = null;
        if ($overageData['is_exceeded'] && $overageData['overage_bookings'] > 0 && $overageData['rate_per_booking'] > 0) {
            $payment = \App\Services\OverageBillingService::syncBookingOverageInvoice($tenant, $bookingCount);
            if ($payment) {
                $pendingInvoice = [
                    'id' => $payment->id,
                    'invoice_number' => $payment->invoice_number,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency ?? 'INR',
                    'status' => $payment->status,
                    'status_label' => 'Pending (Pay Later)',
                    'type' => $payment->type,
                    'metadata' => $payment->metadata,
                    'payment_link' => $payment->metadata['payment_link'] ?? null,
                    'created_at' => $payment->created_at?->format('M d, Y'),
                ];
            }
        }

        $clientAppUrl = config('app.url', 'https://tidcraft.com');
        $upgradeUrl = rtrim($clientAppUrl, '/') . '/client/purchases/' . $tenant->uuid . '/upgrade';

        return response()->json([
            'status' => 'success',
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'uuid' => $tenant->uuid,
            ],
            'overage' => $overageData,
            'pending_invoice' => $pendingInvoice,
            'upgrade_url' => $upgradeUrl,
        ]);
    }

    /**
     * Get tenant overage billing status and invoice history.
     */
    public function overageStatus(Request $request)
    {
        $tenant = $this->findTenantFromRequest($request);

        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant not found.'
            ], 404);
        }

        // Primary: Query from dedicated tenant_overage_bills table
        $bills = \App\Models\TenantOverageBill::where('tenant_id', $tenant->id)
            ->orderBy('id', 'desc')
            ->get();

        if ($bills->isNotEmpty()) {
            $invoices = $bills->map(function ($b) {
                return [
                    'id' => $b->id,
                    'bill_id' => $b->id,
                    'bill_number' => $b->bill_number,
                    'invoice_number' => $b->bill_number,
                    'bill_type' => $b->bill_type,
                    'type' => $b->bill_type === 'bookings' ? 'overage_bookings' : 'overage_orders',
                    'billing_cycle' => $b->billing_cycle,
                    'period_label' => $b->period_label,
                    'included_quota' => $b->included_quota,
                    'total_usage' => $b->total_usage,
                    'overage_units' => $b->overage_units,
                    'rate_per_unit' => (float) $b->rate_per_unit,
                    'amount' => (float) $b->total_amount,
                    'subtotal' => (float) $b->subtotal,
                    'tax_amount' => (float) $b->tax_amount,
                    'currency' => $b->currency ?? 'INR',
                    'status' => $b->status,
                    'is_paid' => $b->isPaid(),
                    'payment_method' => $b->payment_method,
                    'transaction_id' => $b->transaction_id,
                    'payment_link' => $b->payment_link,
                    'due_date' => $b->due_date?->format('M d, Y'),
                    'paid_at' => $b->paid_at?->format('M d, Y'),
                    'created_at' => $b->created_at?->format('M d, Y'),
                    'metadata' => $b->metadata,
                ];
            });
        } else {
            // Fallback to payments table if no bills created yet
            $invoices = \App\Models\Payment::where('tenant_id', $tenant->id)
                ->whereIn('type', ['overage_orders', 'overage_bookings'])
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($p) {
                    $meta = !empty($p->metadata) ? (is_array($p->metadata) ? $p->metadata : json_decode($p->metadata, true)) : [];
                    return [
                        'id' => $p->id,
                        'bill_id' => $p->overage_bill_id ?? $p->id,
                        'bill_number' => $p->invoice_number,
                        'invoice_number' => $p->invoice_number,
                        'bill_type' => $p->type === 'overage_bookings' ? 'bookings' : 'orders',
                        'type' => $p->type,
                        'billing_cycle' => $p->billing_cycle ?? 'monthly',
                        'period_label' => $meta['period_label'] ?? date('M Y'),
                        'included_quota' => $meta['included_bookings'] ?? $meta['included_orders'] ?? 0,
                        'total_usage' => $meta['total_bookings'] ?? $meta['total_orders'] ?? 0,
                        'overage_units' => $meta['overage_bookings'] ?? $meta['overage_orders'] ?? 0,
                        'rate_per_unit' => (float) ($meta['rate_per_booking'] ?? $meta['rate_per_order'] ?? 0),
                        'amount' => (float) $p->amount,
                        'subtotal' => (float) $p->amount,
                        'tax_amount' => 0.00,
                        'currency' => $p->currency ?? 'INR',
                        'status' => $p->status,
                        'is_paid' => in_array(strtolower($p->status), ['success', 'paid']),
                        'payment_method' => $p->payment_method,
                        'transaction_id' => $p->transaction_id,
                        'payment_link' => $meta['payment_link'] ?? null,
                        'due_date' => null,
                        'paid_at' => null,
                        'created_at' => $p->created_at?->format('M d, Y'),
                        'metadata' => $meta,
                    ];
                });
        }

        $plan = $tenant->plan;
        return response()->json([
            'status' => 'success',
            'rate_per_order' => (float) ($plan?->additional_order_price ?? 0),
            'rate_per_booking' => (float) ($plan?->additional_booking_price ?? 0),
            'max_orders_monthly' => (int) ($plan?->max_orders_monthly ?? 0),
            'max_orders_annual' => (int) ($plan?->max_orders ?? 0),
            'max_bookings_monthly' => (int) ($plan?->max_bookings_monthly ?? 0),
            'max_bookings_annual' => (int) ($plan?->max_bookings ?? 0),
            'invoices' => $invoices,
        ]);
    }

    /**
     * Initiate or generate payment link for a pending overage bill directly for a tenant.
     */
    public function payOverage(Request $request)
    {
        $tenant = $this->findTenantFromRequest($request);

        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        // Look up bill from tenant_overage_bills first
        $bill = null;
        if ($request->filled('bill_id')) {
            $bill = \App\Models\TenantOverageBill::where('tenant_id', $tenant->id)->where('id', $request->bill_id)->first();
        } elseif ($request->filled('bill_number')) {
            $bill = \App\Models\TenantOverageBill::where('tenant_id', $tenant->id)->where('bill_number', $request->bill_number)->first();
        } elseif ($request->filled('payment_id')) {
            $bill = \App\Models\TenantOverageBill::where('tenant_id', $tenant->id)
                ->whereHas('payments', function ($pq) use ($request) {
                    $pq->where('id', $request->payment_id);
                })
                ->first();
        }

        if (!$bill) {
            $bill = \App\Models\TenantOverageBill::where('tenant_id', $tenant->id)
                ->where('status', 'pending')
                ->latest('id')
                ->first();
        }

        if ($bill && $bill->isPaid()) {
            return response()->json(['status' => 'error', 'message' => 'This bill has already been paid.'], 400);
        }

        // Corresponding payment
        $payment = null;
        if ($bill) {
            $payment = \App\Models\Payment::where('overage_bill_id', $bill->id)->latest('id')->first();
        }
        if (!$payment) {
            if ($request->filled('payment_id')) {
                $payment = \App\Models\Payment::where('tenant_id', $tenant->id)->where('id', $request->payment_id)->first();
            } else {
                $payment = \App\Models\Payment::where('tenant_id', $tenant->id)
                    ->whereIn('type', ['overage_orders', 'overage_bookings'])
                    ->where('status', 'pending')
                    ->latest('id')
                    ->first();
            }
        }

        if (!$bill && !$payment) {
            return response()->json(['status' => 'error', 'message' => 'No pending overage invoice found for this store.'], 404);
        }

        if ($payment && in_array(strtolower($payment->status), ['success', 'paid'])) {
            return response()->json(['status' => 'error', 'message' => 'This invoice has already been paid.'], 400);
        }

        // Generate payment link
        $link = null;
        if ($payment) {
            $link = ($payment->type === 'overage_bookings')
                ? \App\Services\OverageBillingService::generateBookingPaymentLink($tenant, $payment)
                : \App\Services\OverageBillingService::generatePaymentLink($tenant, $payment);
        }

        if ($link) {
            if ($bill) {
                $bill->payment_link = $link;
                $bill->save();
            }
            if ($payment) {
                $meta = $payment->metadata ?? [];
                $meta['payment_link'] = $link;
                $payment->metadata = $meta;
                $payment->save();
            }
        }

        $saasAppUrl = config('app.url', 'https://tidcraft.com');
        $portalPayUrl = rtrim($saasAppUrl, '/') . '/client/purchases/' . $tenant->uuid . '/renew';
        $finalLink = $link ?? ($bill?->payment_link ?? ($payment?->metadata['payment_link'] ?? $portalPayUrl));

        return response()->json([
            'status' => 'success',
            'message' => 'Overage invoice payment link generated.',
            'data' => [
                'tenant_id' => $tenant->id,
                'tenant_uuid' => $tenant->uuid,
                'bill_id' => $bill?->id,
                'bill_number' => $bill?->bill_number,
                'payment_id' => $payment?->id,
                'invoice_number' => $bill?->bill_number ?? $payment?->invoice_number,
                'amount' => (float) ($bill?->total_amount ?? $payment?->amount),
                'currency' => $bill?->currency ?? $payment?->currency ?? 'INR',
                'status' => $bill?->status ?? $payment?->status,
                'payment_link' => $finalLink,
            ]
        ]);
    }

    /**
     * Resolve feature flags based on plan name, explicit features array, and white-label settings.
     */
    protected function resolveFeatureFlags(?\App\Models\Plan $plan): array
    {
        if (!$plan) {
            return [
                'owner_wallet' => true,
                'watchman_management' => true,
                'ev_charging' => true,
                'owner_wise_commission' => true,
                'white_label_customer_app' => true,
                'white_label_owner_app' => true,
                'white_label_watchman_app' => true,
            ];
        }

        $featuresList = is_string($plan->features) ? json_decode($plan->features, true) : ($plan->features ?? []);
        $featuresList = is_array($featuresList) ? $featuresList : [];
        $planName = strtolower($plan->name ?? '');
        $isGrowthOrHigher = str_contains($planName, 'growth') || str_contains($planName, 'enterprise') || str_contains($planName, 'pro');

        $hasFeature = function($keyword) use ($featuresList) {
            foreach ($featuresList as $f) {
                if (is_string($f) && stripos($f, $keyword) !== false) return true;
            }
            return false;
        };

        $whiteLabeledApp = is_string($plan->has_white_labeled_app) ? json_decode($plan->has_white_labeled_app, true) : ($plan->has_white_labeled_app ?? []);

        return [
            'owner_wallet' => $isGrowthOrHigher || $hasFeature('wallet') || $hasFeature('earnings'),
            'watchman_management' => $isGrowthOrHigher || $hasFeature('watchman management') || $hasFeature('slot assignment'),
            'ev_charging' => $isGrowthOrHigher || $hasFeature('ev charging') || $hasFeature('ev'),
            'owner_wise_commission' => $isGrowthOrHigher || $hasFeature('commission settings') || $hasFeature('owner-wise commission'),
            'white_label_customer_app' => true,
            'white_label_owner_app' => $isGrowthOrHigher || !empty($whiteLabeledApp['owner_app']) || $hasFeature('white-labeled owner app'),
            'white_label_watchman_app' => $isGrowthOrHigher || !empty($whiteLabeledApp['watchman_app']) || $hasFeature('white-labeled watchman app'),
        ];
    }

    /**
     * Helper to resolve tenant from domain, tenant_key, or uuid
     */
    protected function findTenantFromRequest(Request $request): ?Tenant
    {
        $domain = $request->input('domain') ?? $request->query('domain') ?? $request->header('X-Tenant-Domain');
        $tenantKey = $request->input('tenant_key') ?? $request->query('tenant_key');
        $uuid = $request->input('uuid') ?? $request->query('uuid');

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
