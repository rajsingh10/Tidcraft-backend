<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Payment;
use App\Models\Plan;
use Illuminate\Support\Facades\Log;

class OverageBillingService
{
    /**
     * Calculate overage orders and accrued fee for a tenant based on current order count.
     */
    public static function calculateOverage(Tenant $tenant, int $currentOrdersCount): array
    {
        $plan = $tenant->plan;
        $subscription = $tenant->subscriptions()
            ->whereIn('status', ['active', 'expired', 'past_due'])
            ->latest('id')
            ->first();

        $billingCycle = $subscription->billing_cycle ?? 'monthly';
        $isAnnual = in_array(strtolower($billingCycle), ['yearly', 'annual']);

        $includedOrders = $isAnnual
            ? (int) ($plan->max_orders ?? -1)
            : (isset($plan->max_orders_monthly) && (int)$plan->max_orders_monthly > 0 ? (int)$plan->max_orders_monthly : (int)($plan->max_orders ?? -1));

        $ratePerOrder = (float) ($plan->additional_order_price ?? 0);

        $isDemo = (bool) ($tenant->is_demo ?? false) || stripos($plan?->name ?? '', 'demo') !== false;

        if ($isDemo || $includedOrders <= 0 || $ratePerOrder <= 0) {
            // Unlimited orders or demo tenant or no additional order rate configured
            return [
                'has_limit' => false,
                'is_demo' => $isDemo,
                'included_orders' => -1,
                'current_orders' => $currentOrdersCount,
                'overage_orders' => 0,
                'rate_per_order' => $ratePerOrder,
                'accrued_amount' => 0.00,
                'currency' => 'INR',
                'usage_percent' => 0,
                'is_warning' => false,
                'is_exceeded' => false,
                'billing_cycle' => $billingCycle,
            ];
        }

        $overageOrders = max(0, $currentOrdersCount - $includedOrders);
        $accruedAmount = round($overageOrders * $ratePerOrder, 2);
        $usagePercent = round(($currentOrdersCount / $includedOrders) * 100);

        return [
            'has_limit' => true,
            'included_orders' => $includedOrders,
            'current_orders' => $currentOrdersCount,
            'overage_orders' => $overageOrders,
            'rate_per_order' => $ratePerOrder,
            'accrued_amount' => $accruedAmount,
            'currency' => 'INR',
            'usage_percent' => $usagePercent,
            'is_warning' => $usagePercent >= 80 && $usagePercent < 100,
            'is_exceeded' => $usagePercent >= 100,
            'billing_cycle' => $billingCycle,
        ];
    }

    /**
     * Create or update the pending overage invoice for the tenant.
     * Prevents duplicate spam invoices by updating any active 'pending' overage bill for this billing cycle.
     */
    public static function syncOverageInvoice(Tenant $tenant, int $currentOrdersCount): ?Payment
    {
        $overage = self::calculateOverage($tenant, $currentOrdersCount);

        if (!$overage['is_exceeded'] || $overage['overage_orders'] <= 0 || $overage['rate_per_order'] <= 0) {
            return null;
        }

        $periodLabel = date('M Y');
        $plan = $tenant->plan;
        $overageOrders = $overage['overage_orders'];
        $rate = $overage['rate_per_order'];
        $amount = $overage['accrued_amount'];
        $billingCycle = $overage['billing_cycle'];

        // Search for existing pending overage invoice for this tenant
        $payment = Payment::where('tenant_id', $tenant->id)
            ->where('type', 'overage_orders')
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        $metadata = [
            'plan_id' => $plan?->id,
            'plan_name' => $plan?->name ?? 'Default',
            'included_orders' => $overage['included_orders'],
            'total_orders' => $currentOrdersCount,
            'overage_orders' => $overageOrders,
            'rate_per_order' => $rate,
            'billing_cycle' => $billingCycle,
            'period_label' => $periodLabel,
            'description' => "Post-paid Additional Orders: {$overageOrders} orders over {$overage['included_orders']} included limit at ₹{$rate}/order",
        ];

        if ($payment) {
            $payment->update([
                'amount' => $amount,
                'metadata' => array_merge($payment->metadata ?? [], $metadata),
            ]);
            Log::info("Updated existing pending overage invoice #{$payment->id} for tenant #{$tenant->id}: {$overageOrders} extra orders = ₹{$amount}");
        } else {
            $payment = Payment::create([
                'tenant_id' => $tenant->id,
                'amount' => $amount,
                'currency' => 'INR',
                'billing_cycle' => $billingCycle,
                'payment_method' => 'razorpay',
                'status' => 'pending',
                'type' => 'overage_orders',
                'metadata' => $metadata,
            ]);
            Log::info("Generated new pending overage invoice #{$payment->id} for tenant #{$tenant->id}: {$overageOrders} extra orders = ₹{$amount}");
        }

        // Try generating Razorpay link if possible
        $paymentLink = self::generatePaymentLink($tenant, $payment);
        if ($paymentLink) {
            $meta = $payment->metadata ?? [];
            $meta['payment_link'] = $paymentLink;
            $payment->metadata = $meta;
            $payment->save();
        }

        return $payment;
    }

    /**
     * Generate Razorpay payment link for overage invoice.
     */
    public static function generatePaymentLink(Tenant $tenant, Payment $payment): ?string
    {
        $razorpaySettings = \App\Models\Setting::whereIn('key', ['razorpay_key_id', 'razorpay_key_secret', 'razorpay_active'])
            ->pluck('value', 'key')
            ->toArray();

        $isActive = isset($razorpaySettings['razorpay_active']) && in_array($razorpaySettings['razorpay_active'], ['true', '1', true, 1], true);
        if (!$isActive) return null;

        $keyId = $razorpaySettings['razorpay_key_id'] ?? null;
        $keySecret = $razorpaySettings['razorpay_key_secret'] ?? null;

        if ($keyId && $keySecret) {
            try {
                $api = new \Razorpay\Api\Api($keyId, $keySecret);
                $paymentLinkData = [
                    'amount' => (int) ($payment->amount * 100),
                    'currency' => $payment->currency ?? 'INR',
                    'description' => "Overage Orders Invoice #{$payment->invoice_number} ({$tenant->name})",
                    'customer' => array_filter([
                        'name' => $tenant->business_name,
                        'email' => $tenant->primary_contact_email,
                        'contact' => $tenant->phone_number,
                    ]),
                    'notify' => ['email' => true, 'sms' => true],
                    'reminder_enable' => true,
                ];
                return $api->paymentLink->create($paymentLinkData)->short_url;
            } catch (\Throwable $e) {
                Log::warning('Razorpay overage link generation notice: ' . $e->getMessage());
            }
        }

        return null;
    }
}
