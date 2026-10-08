<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantOverageBill extends Model
{
    use SoftDeletes;

    protected $table = 'tenant_overage_bills';

    protected $fillable = [
        'bill_number',
        'tenant_id',
        'client_id',
        'product_id',
        'bill_type',
        'billing_cycle',
        'period_label',
        'included_quota',
        'total_usage',
        'overage_units',
        'rate_per_unit',
        'subtotal',
        'tax_amount',
        'total_amount',
        'currency',
        'status',
        'payment_method',
        'transaction_id',
        'order_id',
        'payment_link',
        'paid_at',
        'due_date',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'due_date' => 'datetime',
        'rate_per_unit' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'overage_bill_id');
    }

    public function isPaid(): bool
    {
        return in_array(strtolower($this->status), ['paid', 'success'], true);
    }

    /**
     * Mark bill as paid and update gateway fields.
     */
    public function markAsPaid(?string $transactionId = null, ?string $paymentMethod = 'razorpay'): void
    {
        $this->update([
            'status' => 'paid',
            'transaction_id' => $transactionId ?? $this->transaction_id,
            'payment_method' => $paymentMethod ?? $this->payment_method,
            'paid_at' => now(),
        ]);
    }

    /**
     * Generate sequential unique bill number (e.g. OVB-2026-001).
     */
    public static function generateBillNumber(string $prefix = 'OVB'): string
    {
        $year = date('Y');
        $lastBill = self::withTrashed()
            ->where('bill_number', 'LIKE', "{$prefix}-{$year}-%")
            ->latest('id')
            ->first();

        $nextSeq = 1;
        if ($lastBill && preg_match('/-(\d+)$/', $lastBill->bill_number, $matches)) {
            $nextSeq = ((int) $matches[1]) + 1;
        }

        return sprintf('%s-%s-%03d', $prefix, $year, $nextSeq);
    }
}
