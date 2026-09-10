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
        return $this->hasMany(Payment::class);
    }

    public function database()
    {
        return $this->hasOne(TenantDatabase::class);
    }

    public function provisioningLogs()
    {
        return $this->hasMany(ProvisioningLog::class);
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
     * Firestore named database id (hyphens only), e.g. "tidcraft-abc".
     */
    public function firestoreDatabaseId(): string
    {
        $id = strtolower(str_replace('_', '-', $this->provisionedDatabaseName()));
        $id = preg_replace('/[^a-z0-9-]/', '-', $id);
        $id = trim($id, '-');

        if (strlen($id) < 4) {
            $id .= '-db';
        }

        return substr($id, 0, 63);
    }
}
