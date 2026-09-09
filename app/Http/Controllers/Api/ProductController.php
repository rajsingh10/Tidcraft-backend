<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with('category')->get()->map(function ($product) {
            if (is_array($product->images)) {
                $product->images = array_map(function ($path) {
                    return url('storage/' . $path);
                }, $product->images);
            }
            $product->product_category_name = $product->category ? $product->category->name : null;
            return $product;
        });

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                if (!$file->isValid()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Upload failed for image {$index}. PHP Error Code: " . $file->getError()
                    ], 422);
                }
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'starting_price' => 'nullable|numeric|min:0',
            'badge' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('images')) {
            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $imagePaths[] = $path;
            }
            $data['images'] = $imagePaths; 
        }

        $product = Product::create($data);

        if (is_array($product->images)) {
            $product->images = array_map(function ($path) {
                return url('storage/' . $path);
            }, $product->images);
        }

        $product->load('category');
        $product->product_category_name = $product->category ? $product->category->name : null;

        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully.',
            'data' => $product
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        if (is_array($product->images)) {
            $product->images = array_map(function ($path) {
                return url('storage/' . $path);
            }, $product->images);
        }

        $product->load('category');
        $product->product_category_name = $product->category ? $product->category->name : null;

        return response()->json([
            'status' => 'success',
            'data' => $product
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                if (!$file->isValid()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Upload failed for image {$index}. PHP Error Code: " . $file->getError()
                    ], 422);
                }
            }
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'starting_price' => 'nullable|numeric|min:0',
            'badge' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('images')) {
            $imagePaths = [];
            // Optional: You could delete old images here if you want to replace them completely
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $imagePaths[] = $path;
            }
            
            // To append images instead of replacing: 
            // $existingImages = $product->images ?? [];
            // $data['images'] = array_merge($existingImages, $imagePaths);
            
            // For now, this will replace the existing images with the newly uploaded ones.
            $data['images'] = $imagePaths;
        }

        $product->update($data);

        if (is_array($product->images)) {
            $product->images = array_map(function ($path) {
                return url('storage/' . $path);
            }, $product->images);
        }

        $product->load('category');
        $product->product_category_name = $product->category ? $product->category->name : null;

        return response()->json([
            'status' => 'success',
            'message' => 'Product updated successfully.',
            'data' => $product
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully.'
        ]);
    }
}
