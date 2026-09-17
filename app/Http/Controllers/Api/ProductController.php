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
        $products = Product::with(['category', 'productFirebaseProject'])->get()->map(function ($product) {
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
     * Display a listing of the resource for public access.
     */
    public function publicIndex()
    {
        $products = Product::with(['category'])->get()->map(function ($product) {
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
            'frontend_path' => 'nullable|string|max:255',
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

        $product->load(['category', 'productFirebaseProject']);
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
            $files = $request->file('images');
            if (is_array($files)) {
                foreach ($files as $index => $file) {
                    if ($file && !$file->isValid()) {
                        return response()->json([
                            'status' => 'error',
                            'message' => "Upload failed for image {$index}. PHP Error Code: " . $file->getError()
                        ], 422);
                    }
                }
            }
        }

        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'starting_price' => 'nullable|numeric|min:0',
            'badge' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'frontend_path' => 'nullable|string|max:255',
            'images' => 'nullable|array',
        ];

        $imagesData = $request->all()['images'] ?? null;
        if (is_array($imagesData)) {
            foreach ($imagesData as $key => $value) {
                if ($request->hasFile("images.$key")) {
                    $rules["images.$key"] = 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048';
                } else {
                    $rules["images.$key"] = 'string';
                }
            }
        }

        $request->validate($rules);

        $data = $request->all();

        if (is_array($imagesData)) {
            $imagePaths = [];
            foreach ($imagesData as $key => $value) {
                if ($request->hasFile("images.$key")) {
                    $file = $request->file("images.$key");
                    if ($file) {
                        $imagePaths[] = $file->store('products', 'public');
                    }
                } else if (is_string($value)) {
                    $storageUrl = url('storage') . '/';
                    if (str_starts_with($value, $storageUrl)) {
                        $imagePaths[] = str_replace($storageUrl, '', $value);
                    } else {
                        $imagePaths[] = $value;
                    }
                }
            }
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
     * Get the Firebase configuration for the Product.
     */
    public function getFirebase(Product $product)
    {
        $firebaseProject = $product->productFirebaseProject;

        if (!$firebaseProject) {
            return response()->json([
                'status' => 'success',
                'data' => null
            ]);
        }

        $payload = $firebaseProject->toArray();
        $payload['has_service_account'] = filled($firebaseProject->service_account_json);

        return response()->json([
            'status' => 'success',
            'data' => $payload
        ]);
    }

    /**
     * Update the Firebase configuration for the Product (Master Project).
     */
    public function updateFirebase(Request $request, Product $product)
    {
        $request->validate([
            'firebase_project_id' => 'required|string|max:255',
            'firebase_project_name' => 'nullable|string|max:255',
            'firebase_app_id' => 'nullable|string|max:255',
            'firebase_api_key' => 'nullable|string|max:255',
            'firebase_auth_domain' => 'nullable|string|max:255',
            'firebase_storage_bucket' => 'nullable|string|max:255',
            'firebase_messaging_sender_id' => 'nullable|string|max:255',
            'firebase_location_id' => 'nullable|string|max:64',
            'firebase_db_collection' => 'nullable',
            'service_account_json' => 'nullable',
        ]);

        $data = $request->only([
            'firebase_project_id',
            'firebase_project_name',
            'firebase_app_id',
            'firebase_api_key',
            'firebase_auth_domain',
            'firebase_storage_bucket',
            'firebase_messaging_sender_id',
            'firebase_location_id',
        ]);

        if ($request->hasFile('firebase_db_collection')) {
            $file = $request->file('firebase_db_collection');
            $extension = $file->getClientOriginalExtension() ?: 'json';
            $filename = \Illuminate\Support\Str::random(40) . '.' . $extension;
            $data['firebase_db_collection'] = $file->storeAs('products/db_collections', $filename, 'public');
        } elseif ($request->has('firebase_db_collection') && !empty($request->input('firebase_db_collection'))) {
            $input = $request->input('firebase_db_collection');
            // If it's already an existing path, just keep it.
            if (is_string($input) && str_starts_with($input, 'products/db_collections/')) {
                $data['firebase_db_collection'] = $input;
            } 
            // If it's raw JSON (string or array), save it as a new file.
            elseif (is_array($input) || (is_string($input) && (str_starts_with(trim($input), '{') || str_starts_with(trim($input), '[')))) {
                $content = is_array($input) ? json_encode($input) : $input;
                $filename = \Illuminate\Support\Str::random(40) . '.json';
                \Illuminate\Support\Facades\Storage::disk('public')->put('products/db_collections/' . $filename, $content);
                $data['firebase_db_collection'] = 'products/db_collections/' . $filename;
            }
        }

        $serviceAccountJson = $this->extractServiceAccountJson($request);
        if ($serviceAccountJson !== null) {
            $data['service_account_json'] = $serviceAccountJson;
        }
        
        $data['product_id'] = $product->id;

        $firebaseProject = $product->productFirebaseProject()->updateOrCreate(
            ['product_id' => $product->id],
            $data
        );

        $payload = $firebaseProject->toArray();
        $payload['has_service_account'] = filled($firebaseProject->service_account_json);

        if ($serviceAccountJson !== null) {
            \App\Models\Tenant::where('product_id', $product->id)
                ->whereIn('status', ['provisioning', 'active', 'failed'])
                ->get()
                ->each(fn ($tenant) => \App\Jobs\ProvisionTenantJob::dispatch($tenant));
            \App\Helpers\QueueRunner::runBackground();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Product Firebase configuration updated successfully.',
            'data' => $payload
        ]);
    }

    private function extractServiceAccountJson(Request $request): ?string
    {
        $raw = null;

        if ($request->hasFile('service_account_json')) {
            $raw = file_get_contents($request->file('service_account_json')->getRealPath());
        } elseif ($request->exists('service_account_json') && $request->service_account_json !== null && $request->service_account_json !== '') {
            $raw = $request->service_account_json;
            if (is_array($raw)) {
                $raw = json_encode($raw);
            }
        }

        if ($raw === null) {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || ($decoded['type'] ?? null) !== 'service_account' || empty($decoded['private_key']) || empty($decoded['client_email']) || empty($decoded['project_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'service_account_json' => 'Must be a valid Firebase service account JSON (type, project_id, client_email, private_key).',
            ]);
        }

        return $raw;
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

    /**
     * Get attachments for the product.
     */
    public function getAttachments(Product $product)
    {
        $sourceCodeZips = $product->source_code_zip ?? [];
        if (is_string($sourceCodeZips)) {
            $sourceCodeZips = json_decode($sourceCodeZips, true) ?? [];
        }

        $sourceCodeUrls = array_map(function($path) {
            return [
                'name' => basename($path),
                'url' => asset('storage/' . ltrim($path, '/'))
            ];
        }, (array)$sourceCodeZips);

        $setupDocUrl = null;
        if ($product->setup_document_pdf) {
            $setupDocUrl = [
                'name' => basename($product->setup_document_pdf),
                'url' => asset('storage/' . ltrim($product->setup_document_pdf, '/'))
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'source_code_zip' => $sourceCodeUrls,
                'setup_document_pdf' => $setupDocUrl
            ]
        ]);
    }

    /**
     * Upload app ZIPs and setup document for the product.
     */
    public function uploadAttachments(Request $request, Product $product)
    {
        $data = [];

        if ($request->hasFile('source_code_zip')) {
            $files = $request->file('source_code_zip');
            // If they accidentally sent a single file, make it an array so we can loop over it
            if (!is_array($files)) {
                $files = [$files];
            }
            
            $paths = [];
            foreach ($files as $index => $file) {
                if (!$file->isValid()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Upload failed for source_code_zip file " . ($index + 1) . ". PHP Error Code: " . $file->getError() . ". This usually means the file exceeds upload_max_filesize in php.ini."
                    ], 422);
                }
                if (strtolower($file->getClientOriginalExtension()) !== 'zip') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'All source code zips must be a file of type: zip.'
                    ], 422);
                }
                
                $originalName = $file->getClientOriginalName();
                $paths[] = $file->storeAs('products/attachments', $originalName, 'public');
            }
            $data['source_code_zip'] = $paths;
        }

        if ($request->hasFile('setup_document_pdf')) {
            $files = $request->file('setup_document_pdf');
            if (!is_array($files)) {
                $files = [$files];
            }
            
            $paths = [];
            foreach ($files as $index => $file) {
                if (!$file->isValid()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Upload failed for setup_document_pdf file " . ($index + 1) . ". PHP Error Code: " . $file->getError()
                    ], 422);
                }
                
                $originalName = $file->getClientOriginalName();
                $paths[] = $file->storeAs('products/attachments', $originalName, 'public');
            }
            $data['setup_document_pdf'] = $paths;
        }

        $request->validate([
            'source_code_zip' => 'nullable|array',
            'source_code_zip.*' => 'file|max:1022976', // max 999MB per file
            'setup_document_pdf' => 'nullable|array',
            'setup_document_pdf.*' => 'file|max:20480', // max 20MB per file
        ]);

        if (empty($data)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No valid files provided for upload.'
            ], 422);
        }

        $product->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Product attachments uploaded successfully.',
            'data' => $product->fresh()
        ]);
    }
}
