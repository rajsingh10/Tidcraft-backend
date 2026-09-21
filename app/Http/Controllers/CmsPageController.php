<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use Illuminate\Http\Request;

class CmsPageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pages = CmsPage::paginate(15);
        return response()->json($pages);
    }

    /**
     * Display the specified resource for admin.
     */
    public function showAdmin($slug)
    {
        $cmsPage = CmsPage::where('slug', $slug)->firstOrFail();
        return response()->json($cmsPage);
    }
    
    /**
     * Fetch public CMS page by slug.
     */
    public function showBySlug($slug)
    {
        $cmsPage = CmsPage::where('slug', $slug)
                          ->where('is_active', true)
                          ->where('status', 'published')
                          ->firstOrFail();
                          
        return response()->json($cmsPage);
    }

    /**
     * Insert or update the CMS page by its slug.
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
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'short_description' => 'nullable|string',
            'content' => 'required|array',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'status' => 'nullable|in:draft,published',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'published_at' => 'nullable|date',
        ]);

        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('cms_images', 'public');
            $validated['featured_image'] = $path;
        }

        if (!isset($validated['status'])) {
            $validated['status'] = 'published';
        }
        
        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

        $cmsPage = CmsPage::updateOrCreate(
            ['slug' => $validated['slug']],
            $validated
        );

        return response()->json($cmsPage, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroyAdmin($slug)
    {
        $cmsPage = CmsPage::where('slug', $slug)->firstOrFail();
        $cmsPage->delete();

        return response()->json(['message' => 'CMS Page deleted successfully']);
    }
}
