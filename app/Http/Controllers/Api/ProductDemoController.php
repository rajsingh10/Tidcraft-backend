<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductDemo;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductDemoController extends Controller
{
    /**
     * Display a listing of the resource (By Product).
     */
    public function index($productId)
    {
        $demos = ProductDemo::where('product_id', $productId)->get();
        return response()->json([
            'status' => 'success',
            'data' => $demos
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'platform' => 'nullable|string|max:100',
            'demo_url' => 'nullable|url|max:255',
            'demo_id' => 'nullable|string|max:255',
            'demo_password' => 'nullable|string|max:255',
            'app_store_url' => 'nullable|url|max:255',
            'play_store_url' => 'nullable|url|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['product_id'] = $product->id;
        $validated['screenshots'] = [];

        $demo = ProductDemo::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Demo created successfully.',
            'data' => $demo
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($productId, $id)
    {
        $demo = ProductDemo::where('product_id', $productId)->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $demo
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $productId, $id)
    {
        $demo = ProductDemo::where('product_id', $productId)->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'platform' => 'nullable|string|max:100',
            'demo_url' => 'nullable|url|max:255',
            'demo_id' => 'nullable|string|max:255',
            'demo_password' => 'nullable|string|max:255',
            'app_store_url' => 'nullable|url|max:255',
            'play_store_url' => 'nullable|url|max:255',
            'is_active' => 'boolean',
        ]);

        $demo->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Demo updated successfully.',
            'data' => $demo
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($productId, $id)
    {
        $demo = ProductDemo::where('product_id', $productId)->findOrFail($id);

        if ($demo->screenshots) {
            foreach ($demo->screenshots as $screenshot) {
                Storage::disk('public')->delete($screenshot);
            }
        }

        $demo->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Demo deleted successfully.'
        ]);
    }

    /**
     * Upload screenshots for a demo.
     */
    public function uploadScreenshots(Request $request, $productId, $id)
    {
        $demo = ProductDemo::where('product_id', $productId)->findOrFail($id);

        $request->validate([
            'screenshots' => 'required|array',
            'screenshots.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        $paths = $demo->screenshots ?? [];

        if ($request->hasFile('screenshots')) {
            foreach ($request->file('screenshots') as $file) {
                $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('demos/screenshots', $filename, 'public');
                $paths[] = $path;
            }
        }

        $demo->update(['screenshots' => $paths]);

        return response()->json([
            'status' => 'success',
            'message' => 'Screenshots uploaded successfully.',
            'data' => $demo
        ]);
    }
    
    /**
     * Delete a specific screenshot from a demo.
     */
    public function deleteScreenshot(Request $request, $productId, $id)
    {
        $demo = ProductDemo::where('product_id', $productId)->findOrFail($id);
        
        $request->validate([
            'path' => 'required|string'
        ]);

        $pathToDelete = $request->path;
        $screenshots = $demo->screenshots ?? [];

        if (($key = array_search($pathToDelete, $screenshots)) !== false) {
            unset($screenshots[$key]);
            $screenshots = array_values($screenshots); // re-index
            $demo->update(['screenshots' => $screenshots]);
            
            Storage::disk('public')->delete($pathToDelete);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Screenshot deleted successfully.',
                'data' => $demo
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Screenshot not found.'
        ], 404);
    }
}
