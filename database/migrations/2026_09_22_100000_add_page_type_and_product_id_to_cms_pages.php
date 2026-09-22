<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds page_type and product_id columns to cms_pages for supporting
     * both the main home page CMS and per-product landing page CMS.
     */
    public function up(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            // page_type: 'home' = main Tidcraft homepage, 'product' = per-product landing page
            $table->enum('page_type', ['home', 'product'])->default('home')->after('is_active');

            // product_id: null for home page, set for product-specific pages
            $table->unsignedBigInteger('product_id')->nullable()->after('page_type');

            $table->index(['page_type', 'product_id'], 'cms_pages_type_product_index');
        });

        // Drop the old unique constraint on slug alone and replace with
        // a composite unique constraint (slug + page_type + product_id)
        // so the same slug (e.g. 'hero') can exist for both home and each product.
        Schema::table('cms_pages', function (Blueprint $table) {
            // Drop existing unique index on slug
            try {
                $table->dropUnique(['slug']);
            } catch (\Exception $e) {
                // If the unique index doesn't exist by this name, try the auto-generated one
                try {
                    $table->dropUnique('cms_pages_slug_unique');
                } catch (\Exception $e2) {
                    // Already dropped or doesn't exist
                }
            }

            // Add composite unique: same slug can appear once per (page_type, product_id) context
            $table->unique(['slug', 'page_type', 'product_id'], 'cms_pages_slug_type_product_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropIndex('cms_pages_type_product_index');

            try {
                $table->dropUnique('cms_pages_slug_type_product_unique');
            } catch (\Exception $e) {}

            // Restore original unique on slug alone
            $table->unique('slug');

            $table->dropColumn(['page_type', 'product_id']);
        });
    }
};
