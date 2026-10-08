<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Payment;
use App\Models\TenantOverageBill;
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

        // 1. Create or update primary record in tenant_overage_bills table
        $bill = TenantOverageBill::where('tenant_id', $tenant->id)
            ->where('bill_type', 'orders')
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        $existingMeta = $bill ? ($bill->metadata ?? []) : [];

        $metadata = [
            'plan_id' => $plan?->id,
            'plan_name' => $plan?->name ?? 'Default',
            'included_orders' => $overage['included_orders'],
            'total_orders' => $currentOrdersCount,
            'overage_orders' => $overageOrders,
            'rate_per_order' => $rate,
            'billing_cycle' => $billingCycle,
            'period_label' => $periodLabel,
            'locations_used' => (int) ($tenant->current_locations_count ?? $existingMeta['locations_used'] ?? 0),
            'users_used' => (int) ($tenant->current_users_count ?? $existingMeta['users_used'] ?? 0),
            'storage_used_gb' => (float) ($tenant->current_storage_used ?? $existingMeta['storage_used_gb'] ?? 0),
            'description' => "Post-paid Additional Orders: {$overageOrders} orders over {$overage['included_orders']} included limit at ₹{$rate}/order",
        ];

        if ($bill) {
            $bill->update([
                'included_quota' => $overage['included_orders'],
                'total_usage' => $currentOrdersCount,
                'overage_units' => $overageOrders,
                'rate_per_unit' => $rate,
                'subtotal' => $amount,
                'tax_amount' => 0.00,
                'total_amount' => $amount,
                'period_label' => $periodLabel,
                'billing_cycle' => $billingCycle,
                'metadata' => array_merge($bill->metadata ?? [], $metadata),
            ]);
            Log::info("Updated pending overage bill #{$bill->bill_number} for tenant #{$tenant->id}: {$overageOrders} extra orders = ₹{$amount}");
        } else {
            $bill = TenantOverageBill::create([
                'bill_number' => TenantOverageBill::generateBillNumber('OVB'),
                'tenant_id' => $tenant->id,
                'client_id' => $tenant->client_id,
                'product_id' => $tenant->product_id,
                'bill_type' => 'orders',
                'billing_cycle' => $billingCycle,
                'period_label' => $periodLabel,
                'included_quota' => $overage['included_orders'],
                'total_usage' => $currentOrdersCount,
                'overage_units' => $overageOrders,
                'rate_per_unit' => $rate,
                'subtotal' => $amount,
                'tax_amount' => 0.00,
                'total_amount' => $amount,
                'currency' => 'INR',
                'status' => 'pending',
                'payment_method' => 'razorpay',
                'due_date' => now()->addDays(7),
                'metadata' => $metadata,
            ]);
            Log::info("Generated new pending overage bill #{$bill->bill_number} for tenant #{$tenant->id}: {$overageOrders} extra orders = ₹{$amount}");
        }

        // 2. Synchronize payment record for backwards compatibility
        $payment = Payment::where('tenant_id', $tenant->id)
            ->where(function ($q) use ($bill) {
                $q->where('overage_bill_id', $bill->id)
                  ->orWhere(function ($q2) {
                      $q2->where('type', 'overage_orders')->where('status', 'pending');
                  });
            })
            ->latest('id')
            ->first();

        if ($payment) {
            $payment->update([
                'overage_bill_id' => $bill->id,
                'amount' => $amount,
                'metadata' => array_merge($payment->metadata ?? [], $metadata, ['overage_bill_id' => $bill->id, 'bill_number' => $bill->bill_number]),
            ]);
            Log::info("Updated existing pending overage invoice #{$payment->id} for tenant #{$tenant->id}: {$overageOrders} extra orders = ₹{$amount}");
        } else {
            $payment = Payment::create([
                'tenant_id' => $tenant->id,
                'overage_bill_id' => $bill->id,
                'amount' => $amount,
                'currency' => 'INR',
                'billing_cycle' => $billingCycle,
                'payment_method' => 'razorpay',
                'status' => 'pending',
                'type' => 'overage_orders',
                'metadata' => array_merge($metadata, ['overage_bill_id' => $bill->id, 'bill_number' => $bill->bill_number]),
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

            $bill->payment_link = $paymentLink;
            $bill->save();
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

    /**
     * Calculate overage bookings and accrued fee for a tenant based on current booking count (ParkMeApp).
     */
    public static function calculateBookingOverage(Tenant $tenant, int $currentBookingsCount): array
    {
        $plan = $tenant->plan;
        $subscription = $tenant->subscriptions()
            ->whereIn('status', ['active', 'expired', 'past_due'])
            ->latest('id')
            ->first();

        $billingCycle = $subscription->billing_cycle ?? 'monthly';
        $isAnnual = in_array(strtolower($billingCycle), ['yearly', 'annual']);

        $includedBookings = $isAnnual
            ? (int) ($plan->max_bookings ?? $plan->max_orders ?? -1)
            : (isset($plan->max_bookings_monthly) && (int)$plan->max_bookings_monthly > 0 
                ? (int)$plan->max_bookings_monthly 
                : ((int)($plan->max_bookings ?? 0) > 0 
                    ? (int)$plan->max_bookings 
                    : (isset($plan->max_orders_monthly) && (int)$plan->max_orders_monthly > 0 ? (int)$plan->max_orders_monthly : (int)($plan->max_orders ?? -1))));

        $ratePerBooking = (float) ($plan->additional_booking_price ?? $plan->additional_order_price ?? 0);

        $isDemo = (bool) ($tenant->is_demo ?? false) || stripos($plan?->name ?? '', 'demo') !== false;

        if ($isDemo || $includedBookings <= 0 || $ratePerBooking <= 0) {
            return [
                'has_limit' => false,
                'is_demo' => $isDemo,
                'included_bookings' => -1,
                'current_bookings' => $currentBookingsCount,
                'overage_bookings' => 0,
                'rate_per_booking' => $ratePerBooking,
                'accrued_amount' => 0.00,
                'currency' => 'INR',
                'usage_percent' => 0,
                'is_warning' => false,
                'is_exceeded' => false,
                'billing_cycle' => $billingCycle,
            ];
        }

        $overageBookings = max(0, $currentBookingsCount - $includedBookings);
        $accruedAmount = round($overageBookings * $ratePerBooking, 2);
        $usagePercent = round(($currentBookingsCount / $includedBookings) * 100);

        return [
            'has_limit' => true,
            'included_bookings' => $includedBookings,
            'current_bookings' => $currentBookingsCount,
            'overage_bookings' => $overageBookings,
            'rate_per_booking' => $ratePerBooking,
            'accrued_amount' => $accruedAmount,
            'currency' => 'INR',
            'usage_percent' => $usagePercent,
            'is_warning' => $usagePercent >= 80 && $usagePercent < 100,
            'is_exceeded' => $usagePercent >= 100,
            'billing_cycle' => $billingCycle,
        ];
    }

    /**
     * Create or update the pending overage invoice for tenant bookings (ParkMeApp).
     */
    public static function syncBookingOverageInvoice(Tenant $tenant, int $currentBookingsCount): ?Payment
    {
        $overage = self::calculateBookingOverage($tenant, $currentBookingsCount);

        if (!$overage['is_exceeded'] || $overage['overage_bookings'] <= 0 || $overage['rate_per_booking'] <= 0) {
            return null;
        }

        $periodLabel = date('M Y');
        $plan = $tenant->plan;
        $overageBookings = $overage['overage_bookings'];
        $rate = $overage['rate_per_booking'];
        $amount = $overage['accrued_amount'];
        $billingCycle = $overage['billing_cycle'];

        // 1. Create or update primary record in tenant_overage_bills table
        $bill = TenantOverageBill::where('tenant_id', $tenant->id)
            ->where('bill_type', 'bookings')
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        $existingMeta = $bill ? ($bill->metadata ?? []) : [];

        $metadata = [
            'plan_id' => $plan?->id,
            'plan_name' => $plan?->name ?? 'Default',
            'included_bookings' => $overage['included_bookings'],
            'total_bookings' => $currentBookingsCount,
            'overage_bookings' => $overageBookings,
            'rate_per_booking' => $rate,
            'billing_cycle' => $billingCycle,
            'period_label' => $periodLabel,
            'locations_used' => (int) ($tenant->current_locations_count ?? $existingMeta['locations_used'] ?? 0),
            'users_used' => (int) ($tenant->current_users_count ?? $existingMeta['users_used'] ?? 0),
            'storage_used_gb' => (float) ($tenant->current_storage_used ?? $existingMeta['storage_used_gb'] ?? 0),
            'description' => "Post-paid Additional Bookings: {$overageBookings} bookings over {$overage['included_bookings']} included limit at ₹{$rate}/booking",
        ];

        if ($bill) {
            $bill->update([
                'included_quota' => $overage['included_bookings'],
                'total_usage' => $currentBookingsCount,
                'overage_units' => $overageBookings,
                'rate_per_unit' => $rate,
                'subtotal' => $amount,
                'tax_amount' => 0.00,
                'total_amount' => $amount,
                'period_label' => $periodLabel,
                'billing_cycle' => $billingCycle,
                'metadata' => array_merge($bill->metadata ?? [], $metadata),
            ]);
            Log::info("Updated pending booking overage bill #{$bill->bill_number} for tenant #{$tenant->id}: {$overageBookings} extra bookings = ₹{$amount}");
        } else {
            $bill = TenantOverageBill::create([
                'bill_number' => TenantOverageBill::generateBillNumber('BK-OVB'),
                'tenant_id' => $tenant->id,
                'client_id' => $tenant->client_id,
                'product_id' => $tenant->product_id,
                'bill_type' => 'bookings',
                'billing_cycle' => $billingCycle,
                'period_label' => $periodLabel,
                'included_quota' => $overage['included_bookings'],
                'total_usage' => $currentBookingsCount,
                'overage_units' => $overageBookings,
                'rate_per_unit' => $rate,
                'subtotal' => $amount,
                'tax_amount' => 0.00,
                'total_amount' => $amount,
                'currency' => 'INR',
                'status' => 'pending',
                'payment_method' => 'razorpay',
                'due_date' => now()->addDays(7),
                'metadata' => $metadata,
            ]);
            Log::info("Generated new pending booking overage bill #{$bill->bill_number} for tenant #{$tenant->id}: {$overageBookings} extra bookings = ₹{$amount}");
        }

        // 2. Synchronize payment record for backwards compatibility
        $payment = Payment::where('tenant_id', $tenant->id)
            ->where(function ($q) use ($bill) {
                $q->where('overage_bill_id', $bill->id)
                  ->orWhere(function ($q2) {
                      $q2->where('type', 'overage_bookings')->where('status', 'pending');
                  });
            })
            ->latest('id')
            ->first();

        if ($payment) {
            $payment->update([
                'overage_bill_id' => $bill->id,
                'amount' => $amount,
                'metadata' => array_merge($payment->metadata ?? [], $metadata, ['overage_bill_id' => $bill->id, 'bill_number' => $bill->bill_number]),
            ]);
            Log::info("Updated existing pending overage invoice #{$payment->id} for tenant #{$tenant->id}: {$overageBookings} extra bookings = ₹{$amount}");
        } else {
            $payment = Payment::create([
                'tenant_id' => $tenant->id,
                'overage_bill_id' => $bill->id,
                'amount' => $amount,
                'currency' => 'INR',
                'billing_cycle' => $billingCycle,
                'payment_method' => 'razorpay',
                'status' => 'pending',
                'type' => 'overage_bookings',
                'metadata' => array_merge($metadata, ['overage_bill_id' => $bill->id, 'bill_number' => $bill->bill_number]),
            ]);
            Log::info("Generated new pending overage invoice #{$payment->id} for tenant #{$tenant->id}: {$overageBookings} extra bookings = ₹{$amount}");
        }

        // Try generating Razorpay link if possible
        $paymentLink = self::generateBookingPaymentLink($tenant, $payment);
        if ($paymentLink) {
            $meta = $payment->metadata ?? [];
            $meta['payment_link'] = $paymentLink;
            $payment->metadata = $meta;
            $payment->save();

            $bill->payment_link = $paymentLink;
            $bill->save();
        }

        return $payment;
    }

    /**
     * Mark overage bill as paid and synchronize linked Payment records.
     */
    public static function recordBillPayment(TenantOverageBill|int $bill, ?string $transactionId = null, ?string $paymentMethod = 'razorpay', array $extraData = []): TenantOverageBill
    {
        if (is_numeric($bill)) {
            $bill = TenantOverageBill::findOrFail($bill);
        }

        $bill->markAsPaid($transactionId, $paymentMethod);
        
        // Save extra razorpay data to bill metadata
        if (!empty($extraData)) {
            $billMeta = $bill->metadata ?? [];
            $bill->metadata = array_merge($billMeta, $extraData);
            $bill->save();
        }

        // Synchronize linked Payment records
        $payments = Payment::where('overage_bill_id', $bill->id)->get();
        foreach ($payments as $payment) {
            $paymentMeta = $payment->metadata ?? [];
            $payment->update([
                'status' => 'success',
                'transaction_id' => $transactionId,
                'payment_method' => $paymentMethod,
                'metadata' => array_merge($paymentMeta, $extraData)
            ]);
        }

        return $bill;
    }

    /**
     * Generate Razorpay payment link for booking overage invoice.
     */
    public static function generateBookingPaymentLink(Tenant $tenant, Payment $payment): ?string
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
                    'description' => "Overage Bookings Invoice #{$payment->invoice_number} ({$tenant->name})",
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
                Log::warning('Razorpay booking overage link generation notice: ' . $e->getMessage());
            }
        }

        return null;
    }
}
