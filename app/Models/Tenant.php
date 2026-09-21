<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use \App\Traits\Auditable;

    const CREATED_AT = 'create_at';
    const UPDATED_AT = 'update_at';
    const DELETED_AT = 'delete_at';

    protected $appends = ['duration_days', 'remaining_days'];

    protected $fillable = [
        'uuid',
        'client_id',
        'name',
        'tenant_key',
        'business_name',
        'primary_contact_email',
        'phone_number',
        'address',
        'industry',
        'product_id',
        'plan_id',
        'status',
        'create_by',
        'update_by',
        'delete_by',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function domain()
    {
        return $this->hasOne(Domain::class)->oldestOfMany();
    }

    public function getDomainAttribute()
    {
        if ($this->relationLoaded('domains') && $this->domains && $this->domains->isNotEmpty()) {
            return $this->domains->first();
        }
        if ($this->relationLoaded('domain') && $this->getRelation('domain')) {
            return $this->getRelation('domain');
        }
        return $this->domains()->first();
    }

    public function firebaseProject()
    {
        return $this->hasOne(FirebaseProject::class);
    }

    public function addOns()
    {
        return $this->belongsToMany(AddOn::class, 'tenant_add_on');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'tenant_id');
    }

    public function database()
    {
        return $this->hasOne(TenantDatabase::class);
    }

    public function provisioningLogs()
    {
        return $this->hasMany(ProvisioningLog::class);
    }

    public function tenantBackups()
    {
        return $this->hasMany(TenantBackup::class);
    }

    /**
     * Subdomain prefix from the first domain, e.g. "abc" from "abc.tidcraft.app".
     */
    public function subdomainPrefix(): string
    {
        $domain = $this->relationLoaded('domains')
            ? $this->domains->first()
            : $this->domains()->first();

        if ($domain && !empty($domain->domain)) {
            $host = strtolower(trim($domain->domain));
            $host = preg_replace('/^https?:\/\//', '', $host);
            $host = explode('/', $host)[0];
            $prefix = explode('.', $host)[0] ?? '';
            $prefix = preg_replace('/[^a-z0-9_]/', '_', $prefix);

            if ($prefix !== '') {
                return $prefix;
            }
        }

        $fallback = preg_replace('/[^a-z0-9_]/', '_', strtolower((string) ($this->tenant_key ?: 't' . $this->id)));

        return $fallback !== '' ? $fallback : ('t' . $this->id);
    }

    /**
     * Isolated MySQL database name, e.g. "tidcraft_abc".
     */
    public function provisionedDatabaseName(): string
    {
        $name = 'tidcraft_' . $this->subdomainPrefix();

        return substr($name, 0, 64);
    }

    /**
     * Firestore named database id. Firebase only allows [a-z0-9-], e.g. tidcraft-acme.
     */
    public function firestoreDatabaseId(): string
    {
        $prefix = strtolower((string) $this->subdomainPrefix());
        $prefix = str_replace('_', '-', $prefix);
        $prefix = preg_replace('/[^a-z0-9-]/', '-', $prefix);
        $prefix = trim($prefix, '-');

        if ($prefix === '') {
            $prefix = 't' . $this->id;
        }

        return substr('tidcraft-' . $prefix, 0, 63);
    }

    public function getDurationDaysAttribute()
    {
        $subscription = $this->relationLoaded('subscriptions') 
            ? $this->subscriptions->sortByDesc('id')->first()
            : $this->subscriptions()->latest('id')->first();

        if ($subscription && $subscription->start_date && $subscription->end_date) {
            return \Carbon\Carbon::parse($subscription->start_date)->diffInDays(\Carbon\Carbon::parse($subscription->end_date));
        }

        // Fallback for legacy tenants with null end_date
        if ($this->plan && $this->plan->duration_days) {
            return (int) $this->plan->duration_days;
        }

        return 0;
    }

    public function getRemainingDaysAttribute()
    {
        $subscription = $this->relationLoaded('subscriptions') 
            ? $this->subscriptions->sortByDesc('id')->first()
            : $this->subscriptions()->latest('id')->first();

        if ($subscription) {
            $endDate = $subscription->end_date;
            
            // Dynamically calculate end_date if it's missing but we know the plan duration
            if (!$endDate && $this->plan && $this->plan->duration_days) {
                $startDate = $subscription->start_date ? \Carbon\Carbon::parse($subscription->start_date) : $subscription->created_at;
                $endDate = clone $startDate;
                $endDate->addDays($this->plan->duration_days);
            }

            if ($endDate) {
                $days = now()->diffInDays(\Carbon\Carbon::parse($endDate), false);
                return $days > 0 ? (int) ceil($days) : 0;
            }
        }
        return 0;
    }

    /**
     * Quota Checkers (Combines Plan limits + AddOn limits based on AddOn name and limit field)
     */
    public function getMaxUsers(): int
    {
        $planLimit = $this->plan ? (int) $this->plan->max_users : 0;
        if ($planLimit === -1) return -1; // Unlimited

        $addOnsLimit = 0;
        if ($this->relationLoaded('addOns') || $this->exists) {
            foreach ($this->addOns as $addon) {
                if (stripos($addon->name, 'user') !== false) {
                    $addOnsLimit += (int) $addon->limit;
                }
            }
        }
        
        return $planLimit + $addOnsLimit;
    }

    public function getMaxOrders(): int
    {
        $planLimit = $this->plan ? (int) $this->plan->max_orders : 0;
        if ($planLimit === -1) return -1; // Unlimited

        $addOnsLimit = 0;
        if ($this->relationLoaded('addOns') || $this->exists) {
            foreach ($this->addOns as $addon) {
                if (stripos($addon->name, 'order') !== false || stripos($addon->name, 'request') !== false) {
                    $addOnsLimit += (int) $addon->limit;
                }
            }
        }

        return $planLimit + $addOnsLimit;
    }

    public function getStorageLimitGb(): int
    {
        $planLimit = $this->plan ? (int) $this->plan->storage_gb : 0;
        if ($planLimit === -1) return -1; // Unlimited

        $addOnsLimit = 0;
        if ($this->relationLoaded('addOns') || $this->exists) {
            foreach ($this->addOns as $addon) {
                if (stripos($addon->name, 'storage') !== false || stripos($addon->name, 'gb') !== false) {
                    $addOnsLimit += (int) $addon->limit;
                }
            }
        }

        return $planLimit + $addOnsLimit;
    }

    /**
     * Expiration Checker
     */
    public function hasActiveSubscription(): bool
    {
        // Check if tenant is explicitly suspended/blocked
        if (in_array(strtolower($this->status), ['suspended', 'past due', 'past_due', 'expired'])) {
            return false;
        }

        $subscription = $this->relationLoaded('subscriptions') 
            ? $this->subscriptions->sortByDesc('id')->first()
            : $this->subscriptions()->latest('id')->first();

        if (!$subscription) {
            return false;
        }

        // If no end_date, assume lifetime or not yet started properly
        if (!$subscription->end_date) {
            return true;
        }

        return \Carbon\Carbon::parse($subscription->end_date)->isFuture();
    }
}
