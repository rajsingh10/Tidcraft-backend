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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:cms_pages,slug',
            'short_description' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'status' => 'required|in:draft,published',
            'sort_order' => 'nullable|integer',
            'is_active' => 'required|boolean',
            'published_at' => 'nullable|date',
        ]);

        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('cms_images', 'public');
            $validated['featured_image'] = $path;
        }

        $cmsPage = CmsPage::create($validated);

        return response()->json($cmsPage, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(CmsPage $cmsPage)
    {
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, CmsPage $cmsPage)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:cms_pages,slug,' . $cmsPage->id,
            'short_description' => 'nullable|string',
            'content' => 'sometimes|required|string',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'status' => 'sometimes|required|in:draft,published',
            'sort_order' => 'nullable|integer',
            'is_active' => 'sometimes|required|boolean',
            'published_at' => 'nullable|date',
        ]);

        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('cms_images', 'public');
            $validated['featured_image'] = $path;
        } else {
            // Keep the old image if a new one isn't uploaded, but allow nullification if desired
            unset($validated['featured_image']); 
        }

        $cmsPage->update($validated);

        return response()->json($cmsPage);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CmsPage $cmsPage)
    {
        $cmsPage->delete();

        return response()->json(['message' => 'CMS Page deleted successfully']);
    }
}
