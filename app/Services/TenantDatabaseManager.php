<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use App\Models\Tenant;

class TenantDatabaseManager
{
    /**
     * Set the current tenant database connection based on the tenant.
     *
     * @param Tenant $tenant
     * @return void
     */
    public static function connectToTenant(Tenant $tenant)
    {
        $tenantDb = $tenant->database;
        if (!$tenantDb) {
            throw new \Exception("Tenant database configuration not found.");
        }

        // We assume 'tenant' connection exists in config/database.php
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

        // Reconnect
        DB::purge('tenant');
        DB::setDefaultConnection('tenant');
    }

    /**
     * Revert to the master database connection.
     *
     * @return void
     */
    public static function disconnectFromTenant()
    {
        DB::setDefaultConnection(Config::get('database.default'));
        DB::purge('tenant');
    }

    /**
     * Create a new database for the tenant and run migrations.
     *
     * @param Tenant $tenant
     * @return \App\Models\TenantDatabase
     */
    public static function provisionDatabase(Tenant $tenant)
    {
        $dbName = 'tenant_' . str_replace('-', '_', $tenant->uuid);
        
        // Ensure database record is created
        $tenantDb = $tenant->database()->firstOrCreate([
            'tenant_id' => $tenant->id
        ], [
            'database_name' => $dbName,
            'status' => 'creating'
        ]);

        if ($tenantDb->status === 'ready') {
            return $tenantDb;
        }

        try {
            // Create the physical database (assuming master user has privileges)
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Connect to this new database
            self::connectToTenant($tenant);

            // Run migrations
            // We'll run the default migrations for now, or you can specify a path if needed.
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--force' => true,
            ]);

            // Revert back
            self::disconnectFromTenant();

            $tenantDb->update(['status' => 'ready']);

            return $tenantDb;
        } catch (\Exception $e) {
            $tenantDb->update(['status' => 'failed']);
            self::disconnectFromTenant();
            throw $e;
        }
    }
}
