<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $template = DB::table('email_templates')
            ->where('slug', 'Your_Application_is_Ready')
            ->orWhere('id', 8)
            ->first();

        if ($template && !empty($template->content)) {
            $content = $template->content;

            // Make tenant properties null-safe and add safe variable fallbacks
            $search = "\$productName = \$tenant->product ? strtolower(\$tenant->product->name) : '';";
            $replace = "\$clientName = !empty(\$tenant->client?->name) ? \$tenant->client->name : (!empty(\$clientName) ? \$clientName : (\$tenant->business_name ?? 'Client'));\n        \$productName = !empty(\$tenant->product?->name) ? strtolower(\$tenant->product->name) : (!empty(\$product_name) ? strtolower(\$product_name) : '');\n        \$domainUrl = \$domainUrl ?? (\$domain_url ?? config('app.url'));\n        \$adminEmail = \$adminEmail ?? (\$admin_email ?? (\$tenant->client?->email ?? ''));\n        \$adminPassword = \$adminPassword ?? (\$admin_password ?? '********');";

            if (strpos($content, $search) !== false) {
                // Remove the old $clientName line right above it if present to avoid duplication
                $content = str_replace("\$clientName = \$tenant->client->name ?? \$tenant->business_name;\n        " . $search, $replace, $content);
                $content = str_replace($search, $replace, $content);
            }

            DB::table('email_templates')
                ->where('id', $template->id)
                ->update(['content' => $content]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
