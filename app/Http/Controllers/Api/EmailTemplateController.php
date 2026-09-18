<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\AuditLogger;

class EmailTemplateController extends Controller
{
    /**
     * Display a listing of email templates.
     */
    public function index()
    {
        $templates = EmailTemplate::all();
        return response()->json([
            'status' => 'success',
            'data' => $templates
        ]);
    }

    /**
     * Store a newly created email template.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:email_templates,slug|max:255',
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'images' => 'nullable|array', // Assuming it's an array of image paths/URLs
            'status' => 'nullable|in:active,inactive',
        ]);

        $data = $request->except('images');

        // Handle File Uploads for images
        if ($request->hasFile('images')) {
            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('email_templates', 'public');
                $imagePaths[] = '/storage/' . $path;
            }
            $data['images'] = $imagePaths;
        } elseif ($request->has('images') && is_array($request->images)) {
            // Keep as strings if URLs were provided directly
            $data['images'] = $request->images;
        }

        // Generate slug from title if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $data['created_by'] = auth()->id();

        $template = EmailTemplate::create($data);

        // Audit Log
        AuditLogger::log(
            'Email Template Created',
            'Create Email Template',
            "Created email template '{$template->title}'",
            null,
            $template->toArray()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Email template created successfully.',
            'data' => $template
        ], 201);
    }

    /**
     * Display the specified email template.
     */
    public function show($id)
    {
        $template = EmailTemplate::find($id);
        
        if (!$template) {
            // Also allow finding by slug
            $template = EmailTemplate::where('slug', $id)->first();
            if (!$template) {
                return response()->json(['status' => 'error', 'message' => 'Email template not found.'], 404);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $template
        ]);
    }

    /**
     * Update the specified email template.
     */
    public function update(Request $request, $id)
    {
        $template = EmailTemplate::find($id);
        
        if (!$template) {
            $template = EmailTemplate::where('slug', $id)->first();
            if (!$template) {
                return response()->json(['status' => 'error', 'message' => 'Email template not found.'], 404);
            }
        }

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:email_templates,slug,' . $template->id,
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'images' => 'nullable|array',
            'status' => 'nullable|in:active,inactive',
        ]);

        $oldValues = $template->toArray();

        $data = $request->except('images');

        // Handle File Uploads for images
        if ($request->hasFile('images')) {
            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('email_templates', 'public');
                $imagePaths[] = '/storage/' . $path;
            }
            // Optional: If you want to append to existing, you can merge arrays here. 
            // For now, uploading new files replaces the array.
            $data['images'] = $imagePaths;
        } elseif ($request->has('images') && is_array($request->images)) {
            $data['images'] = $request->images;
        }

        if (isset($data['title']) && empty($data['slug']) && !isset($request->slug)) {
            $data['slug'] = Str::slug($data['title']);
        }

        $data['updated_by'] = auth()->id();

        $template->update($data);

        // Audit Log
        AuditLogger::log(
            'Email Template Updated',
            'Update Email Template',
            "Updated email template '{$template->title}'",
            $oldValues,
            $template->toArray()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Email template updated successfully.',
            'data' => $template
        ]);
    }

    /**
     * Remove the specified email template from storage.
     */
    public function destroy($id)
    {
        $template = EmailTemplate::find($id);
        
        if (!$template) {
            $template = EmailTemplate::where('slug', $id)->first();
            if (!$template) {
                return response()->json(['status' => 'error', 'message' => 'Email template not found.'], 404);
            }
        }

        $oldValues = $template->toArray();
        $title = $template->title;
        
        $template->deleted_by = auth()->id();
        $template->save();
        $template->delete();

        // Audit Log
        AuditLogger::log(
            'Email Template Deleted',
            'Delete Email Template',
            "Deleted email template '{$title}'",
            $oldValues,
            null
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Email template deleted successfully.'
        ]);
    }
}
