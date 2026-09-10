<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantDatabaseManager
{
    /**
     * Point the "tenant" connection at this tenant's database without changing
     * the application default connection (queue/jobs must stay on master).
     */
    public static function configureTenantConnection(Tenant $tenant): void
    {
        $tenantDb = $tenant->database;
        if (!$tenantDb) {
            throw new \Exception('Tenant database configuration not found.');
        }

        Config::set('database.connections.tenant.database', $tenantDb->database_name);

        if ($tenantDb->database_host) {
            Config::set('database.connections.tenant.host', $tenantDb->database_host);
        }
        if ($tenantDb->database_port) {
            Config::set('database.connections.tenant.port', $tenantDb->database_port);
        }
        if ($tenantDb->database_username) {
            Config::set('database.connections.tenant.username', $tenantDb->database_username);
        }
        if ($tenantDb->encrypted_database_password) {
            Config::set('database.connections.tenant.password', decrypt($tenantDb->encrypted_database_password));
        }

        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    /**
     * Create the physical MySQL database named tidcraft_{subdomain}.
     */
    public static function createDatabase(Tenant $tenant)
    {
        $dbName = $tenant->provisionedDatabaseName();

        $tenantDb = $tenant->database()->firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'database_name' => $dbName,
                'database_host' => env('DB_HOST', '127.0.0.1'),
                'database_port' => env('DB_PORT', '3306'),
                'database_username' => env('DB_USERNAME', 'root'),
                'encrypted_database_password' => env('DB_PASSWORD') ? encrypt(env('DB_PASSWORD')) : null,
                'status' => 'creating',
            ]
        );

        if ($tenantDb->status !== 'ready' && $tenantDb->database_name !== $dbName) {
            $tenantDb->update([
                'database_name' => $dbName,
                'status' => 'creating',
            ]);
        }

        if ($tenantDb->status === 'ready') {
            $tenant->unsetRelation('database');
            $tenant->load('database');

            return $tenantDb;
        }

        try {
            $safeName = preg_replace('/[^a-zA-Z0-9_]/', '', $dbName);
            if ($safeName === '' || $safeName !== $dbName) {
                throw new \Exception("Invalid tenant database name: {$dbName}");
            }

            DB::statement("CREATE DATABASE IF NOT EXISTS `{$safeName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            $tenantDb->update(['status' => 'creating']);
            $tenant->unsetRelation('database');
            $tenant->load('database');

            return $tenantDb->fresh();
        } catch (\Exception $e) {
            $tenantDb->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Run tenant migrations against tidcraft_{subdomain}.
     */
    public static function migrate(Tenant $tenant): void
    {
        $default = Config::get('database.default');
        self::configureTenantConnection($tenant);

        try {
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('tenant');
        }
    }

    /**
     * Seed the tenant database in the background provisioning flow.
     */
    public static function seed(Tenant $tenant): void
    {
        $default = Config::get('database.default');
        self::configureTenantConnection($tenant);

        Config::set('tenant.provisioning', [
            'tenant_id' => $tenant->id,
            'uuid' => $tenant->uuid,
            'name' => $tenant->business_name,
            'email' => $tenant->primary_contact_email,
            'database_name' => $tenant->database?->database_name,
            'subdomain' => $tenant->subdomainPrefix(),
            'firebase_project_id' => $tenant->firebaseProject?->firebase_project_id,
            'firebase_database_id' => $tenant->firebaseProject?->firebase_database_id,
            'firebase_api_key' => $tenant->firebaseProject?->firebase_api_key,
            'firebase_app_id' => $tenant->firebaseProject?->firebase_app_id,
            'firebase_auth_domain' => $tenant->firebaseProject?->firebase_auth_domain,
            'firebase_storage_bucket' => $tenant->firebaseProject?->firebase_storage_bucket,
            'firebase_messaging_sender_id' => $tenant->firebaseProject?->firebase_messaging_sender_id,
        ]);

        try {
            Artisan::call('db:seed', [
                '--class' => \Database\Seeders\TenantDatabaseSeeder::class,
                '--database' => 'tenant',
                '--force' => true,
            ]);
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('tenant');
        }
    }

    /**
     * Create database, migrate, and seed (legacy combined helper).
     */
    public static function provisionDatabase(Tenant $tenant)
    {
        $tenantDb = self::createDatabase($tenant);

        if ($tenantDb->status === 'ready') {
            return $tenantDb;
        }

        try {
            self::migrate($tenant);
            self::seed($tenant);

            $tenantDb->update(['status' => 'ready']);

            return $tenantDb->fresh();
        } catch (\Exception $e) {
            $tenantDb->update(['status' => 'failed']);
            throw $e;
        }
    }
}
