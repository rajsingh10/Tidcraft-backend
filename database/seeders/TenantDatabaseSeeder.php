<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Seed the tenant (subdomain) database.
     */
    public function run(): void
    {
        $context = config('tenant.provisioning', []);
        $now = now();

        $email = $context['email'] ?? 'admin@example.com';
        $name = $context['name'] ?? 'Tenant Admin';

        $userExists = DB::connection('tenant')->table('users')->where('email', $email)->exists();
        if (!$userExists) {
            DB::connection('tenant')->table('users')->insert([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(env('TENANT_DEFAULT_PASSWORD', 'password')),
                'email_verified_at' => $now,
                'remember_token' => Str::random(10),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $settings = [
            'tenant_uuid' => $context['uuid'] ?? null,
            'tenant_name' => $context['name'] ?? null,
            'subdomain' => $context['subdomain'] ?? null,
            'database_name' => $context['database_name'] ?? null,
            'firebase_project_id' => $context['firebase_project_id'] ?? null,
            'firebase_database_id' => $context['firebase_database_id'] ?? null,
            'firebase_api_key' => $context['firebase_api_key'] ?? null,
            'firebase_app_id' => $context['firebase_app_id'] ?? null,
            'firebase_auth_domain' => $context['firebase_auth_domain'] ?? null,
            'firebase_storage_bucket' => $context['firebase_storage_bucket'] ?? null,
            'firebase_messaging_sender_id' => $context['firebase_messaging_sender_id'] ?? null,
        ];

        foreach ($settings as $key => $value) {
            if ($value === null) {
                continue;
            }

            DB::connection('tenant')->table('tenant_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => is_scalar($value) ? (string) $value : json_encode($value), 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }
}
