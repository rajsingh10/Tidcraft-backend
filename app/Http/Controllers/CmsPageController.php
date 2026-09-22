<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\Product;
use Illuminate\Http\Request;

class CmsPageController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Public / Front-end Endpoints
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fetch a single public CMS page by slug.
     * Optional query param ?product_id=<id> to scope to a product page.
     * If product_id omitted it defaults to home-page context.
     */
    public function showBySlug(Request $request, $slug)
    {
        $query = CmsPage::where('slug', $slug)
                        ->where('is_active', true)
                        ->where('status', 'published');

        if ($request->filled('product_id')) {
            $query->where('page_type', 'product')
                  ->where('product_id', $request->product_id);
        } else {
            $query->where('page_type', 'home')->whereNull('product_id');
        }

        $cmsPage = $query->firstOrFail();

        return response()->json($cmsPage);
    }

    /**
     * Get all published sections for the home page.
     * GET /api/cms-pages/home
     */
    public function homePage()
    {
        $pages = CmsPage::homePage()
                        ->where('is_active', true)
                        ->where('status', 'published')
                        ->orderBy('sort_order')
                        ->get();

        return response()->json($pages);
    }

    /**
     * Get all published sections for a specific product landing page.
     * GET /api/cms-pages/product/{productId}
     */
    public function productPage($productId)
    {
        // Validate product exists
        Product::findOrFail($productId);

        $pages = CmsPage::forProduct($productId)
                        ->where('is_active', true)
                        ->where('status', 'published')
                        ->orderBy('sort_order')
                        ->get();

        return response()->json($pages);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Admin Endpoints
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * List all CMS pages (admin, with pagination).
     * Optional filters: ?page_type=home|product  &product_id=<id>
     */
    public function index(Request $request)
    {
        $query = CmsPage::query();

        if ($request->filled('page_type')) {
            $query->where('page_type', $request->page_type);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $pages = $query->orderBy('sort_order')->paginate(15);

        return response()->json($pages);
    }

    /**
     * Show a single CMS page by slug (admin – includes drafts, inactive).
     * Optional query param ?product_id=<id>  and ?page_type=home|product.
     */
    public function showAdmin(Request $request, $slug)
    {
        $query = CmsPage::where('slug', $slug);

        if ($request->filled('product_id')) {
            $query->where('page_type', 'product')
                  ->where('product_id', $request->product_id);
        } elseif ($request->filled('page_type')) {
            $query->where('page_type', $request->page_type);
        } else {
            // Default to home context when nothing specified
            $query->where('page_type', 'home')->whereNull('product_id');
        }

        $cmsPage = $query->firstOrFail();

        return response()->json($cmsPage);
    }

    /**
     * Insert or update a CMS page.
     * Upsert key: slug + page_type + product_id (matches composite unique index).
     */
    public function save(Request $request)
    {
        // If content is sent as a JSON string (e.g., via multipart/form-data), decode it first
        if (is_string($request->input('content'))) {
            $request->merge([
                'content' => json_decode($request->input('content'), true)
            ]);
        }

        $validated = $request->validate([
            'title'             => 'required|string|max:255',
            'slug'              => 'required|string|max:255',
            'page_type'         => 'nullable|in:home,product',
            'product_id'        => 'nullable|integer|exists:products,id',
            'short_description' => 'nullable|string',
            'content'           => 'required|array',
            'featured_image'    => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'meta_title'        => 'nullable|string|max:255',
            'meta_description'  => 'nullable|string',
            'meta_keywords'     => 'nullable|string',
            'status'            => 'nullable|in:draft,published',
            'sort_order'        => 'nullable|integer',
            'is_active'         => 'nullable|boolean',
            'published_at'      => 'nullable|date',
        ]);

        // Ensure product page has a product_id
        if (($validated['page_type'] ?? 'home') === 'product' && empty($validated['product_id'])) {
            return response()->json([
                'message' => 'product_id is required when page_type is "product".',
                'errors'  => ['product_id' => ['product_id is required for product pages.']],
            ], 422);
        }

        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('cms_images', 'public');
            $validated['featured_image'] = $path;
        }

        $validated['page_type'] = $validated['page_type'] ?? 'home';
        $validated['status']    = $validated['status']    ?? 'published';
        $validated['is_active'] = $validated['is_active'] ?? true;

        // Composite upsert key (matches the unique index)
        $upsertKey = [
            'slug'       => $validated['slug'],
            'page_type'  => $validated['page_type'],
            'product_id' => $validated['product_id'] ?? null,
        ];

        $cmsPage = CmsPage::updateOrCreate($upsertKey, $validated);

        return response()->json($cmsPage, 200);
    }

    /**
     * Delete a CMS page by slug.
     * Optional query param ?product_id=<id> / ?page_type=home|product to scope.
     */
    public function destroyAdmin(Request $request, $slug)
    {
        $query = CmsPage::where('slug', $slug);

        if ($request->filled('product_id')) {
            $query->where('page_type', 'product')
                  ->where('product_id', $request->product_id);
        } elseif ($request->filled('page_type')) {
            $query->where('page_type', $request->page_type);
        } else {
            $query->where('page_type', 'home')->whereNull('product_id');
        }

        $cmsPage = $query->firstOrFail();
        $cmsPage->delete();

        return response()->json(['message' => 'CMS Page deleted successfully']);
    }
}
