<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Mail\DynamicEmail;
use App\Services\EmailPreviewSampleDataProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailPreviewController extends Controller
{
    /**
     * Display the email preview dashboard.
     */
    public function index(Request $request)
    {
        // 1. Gather all database dynamic templates
        $dbTemplates = EmailTemplate::orderBy('title')->get()->map(function ($template) {
            $meta = EmailPreviewSampleDataProvider::getDynamicTemplateData($template->slug);
            return [
                'type' => 'template',
                'id' => $template->id,
                'slug' => $template->slug,
                'title' => $template->title,
                'subject' => $template->subject,
                'status' => $template->status,
                'category' => $meta['category'] ?? 'General',
                'variables' => $meta['variables'] ?? [],
                'default_data' => $meta['replacements'] ?? [],
                'content' => $template->content,
                'updated_at' => $template->updated_at ? $template->updated_at->diffForHumans() : null,
            ];
        })->values();

        // 2. Gather all Blade mailables & views
        $mailableRegistry = EmailPreviewSampleDataProvider::getMailableRegistry();
        $mailables = collect($mailableRegistry)->map(function ($item, $key) {
            return [
                'type' => 'mailable',
                'id' => $key,
                'slug' => $key,
                'title' => $item['title'],
                'class' => $item['class'] ?? null,
                'view' => $item['view'],
                'category' => $item['category'] ?? 'Mailables',
                'description' => $item['description'] ?? '',
                'variables' => $item['variables'] ?? [],
                'default_data' => $item['sample_data'] ?? [],
            ];
        })->values();

        // 3. Determine active item
        $reqType = $request->query('type', 'template');
        $reqId = $request->query('id');

        $activeItem = null;

        if ($reqType === 'template') {
            if ($reqId) {
                $activeItem = $dbTemplates->first(function ($t) use ($reqId) {
                    return $t['id'] == $reqId || $t['slug'] == $reqId;
                });
            }
            if (!$activeItem) {
                $activeItem = $dbTemplates->first() ?? $mailables->first();
            }
        } else {
            if ($reqId) {
                $activeItem = $mailables->first(function ($m) use ($reqId) {
                    return $m['id'] === $reqId;
                });
            }
            if (!$activeItem) {
                $activeItem = $mailables->first() ?? $dbTemplates->first();
            }
        }

        // 4. Group all templates by category for clean tabs / filtering
        $allCategories = collect($dbTemplates)->pluck('category')
            ->merge(collect($mailables)->pluck('category'))
            ->unique()
            ->values();

        return view('email-preview.index', [
            'dbTemplates' => $dbTemplates,
            'mailables' => $mailables,
            'activeItem' => $activeItem,
            'categories' => $allCategories,
        ]);
    }

    /**
     * Render the raw email HTML for iframe or standalone preview.
     */
    public function renderView(Request $request)
    {
        $type = $request->input('type') ?? $request->query('type', 'template');
        $id = $request->input('id') ?? $request->query('id');

        // Allow passing custom data as JSON or array
        $customData = $request->input('data');
        if (is_string($customData)) {
            $decoded = json_decode($customData, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $customData = $decoded;
            }
        }
        $customData = is_array($customData) ? $customData : [];

        try {
            if ($type === 'template') {
                $template = EmailTemplate::find($id);
                if (!$template) {
                    $template = EmailTemplate::where('slug', $id)->first();
                }

                if (!$template) {
                    return response("<div style='font-family:sans-serif; padding:40px; text-align:center;'><h2>Template not found</h2><p>ID or Slug: " . htmlspecialchars($id) . "</p></div>", 404);
                }

                // If user sent updated content in the request (e.g. testing in real-time before saving)
                if ($request->filled('override_content')) {
                    $template = clone $template;
                    $template->content = $request->input('override_content');
                }
                if ($request->filled('override_subject')) {
                    $template->subject = $request->input('override_subject');
                }

                $meta = EmailPreviewSampleDataProvider::getDynamicTemplateData($template->slug);
                $replacements = array_merge($meta['replacements'] ?? [], $customData);

                $dynamicEmail = new DynamicEmail($template, $replacements);
                $html = $dynamicEmail->render();
                $subject = $dynamicEmail->dynamicSubject;

            } else {
                $mailables = EmailPreviewSampleDataProvider::getMailableRegistry();
                if (!isset($mailables[$id])) {
                    return response("<div style='font-family:sans-serif; padding:40px; text-align:center;'><h2>Mailable not found</h2><p>Class: " . htmlspecialchars($id) . "</p></div>", 404);
                }

                $meta = $mailables[$id];
                $data = array_merge($meta['sample_data'] ?? [], $customData);
                $factory = $meta['factory'];

                $result = $factory($data);

                if (is_object($result) && method_exists($result, 'render')) {
                    $html = $result->render();
                    $subject = method_exists($result, 'envelope') ? ($result->envelope()->subject ?? $meta['title']) : ($result->subject ?? $meta['title']);
                } elseif (is_string($result)) {
                    $html = $result;
                    $subject = $meta['title'];
                } else {
                    $html = (string) $result;
                    $subject = $meta['title'];
                }
            }

            if ($request->wantsJson() || $request->input('format') === 'json') {
                return response()->json([
                    'success' => true,
                    'html' => $html,
                    'subject' => $subject ?? '',
                ]);
            }

            return response($html, 200)
                ->header('Content-Type', 'text/html; charset=utf-8');

        } catch (\Throwable $e) {
            Log::error('EmailPreviewController render error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            $errorHtml = "
            <div style='margin: 30px auto; max-width: 700px; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; padding: 24px; color: #9f1239;'>
                <div style='display:flex; align-items:center; margin-bottom: 12px;'>
                    <span style='background:#f43f5e; color:white; border-radius:50%; width:28px; height:28px; display:inline-flex; align-items:center; justify-content:center; font-weight:bold; margin-right:12px;'>!</span>
                    <h3 style='margin:0; font-size:18px;'>Template Render Exception</h3>
                </div>
                <p style='margin: 8px 0; font-size: 14px; line-height: 1.5;'><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                <p style='margin: 8px 0; font-size: 13px; color: #e11d48;'>File: " . htmlspecialchars($e->getFile()) . " (Line " . $e->getLine() . ")</p>
                <div style='margin-top: 16px; background: white; padding: 12px; border-radius: 6px; font-family: monospace; font-size: 12px; overflow-x: auto; color: #334155; border: 1px solid #fbcfe8;'>
                    " . nl2br(htmlspecialchars(substr($e->getTraceAsString(), 0, 800))) . "
                </div>
            </div>";

            if ($request->wantsJson() || $request->input('format') === 'json') {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'html' => $errorHtml,
                ], 500);
            }

            return response($errorHtml, 500)
                ->header('Content-Type', 'text/html; charset=utf-8');
        }
    }

    /**
     * Update an email template directly from the preview dashboard.
     */
    public function updateTemplate(Request $request, $id)
    {
        $template = EmailTemplate::find($id);
        if (!$template) {
            $template = EmailTemplate::where('slug', $id)->first();
        }

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Email template not found.',
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'content' => 'required|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        if (!empty($validated['title'])) {
            $template->title = $validated['title'];
        }
        if (isset($validated['subject'])) {
            $template->subject = $validated['subject'];
        }
        $template->content = $validated['content'];
        if (isset($validated['status'])) {
            $template->status = $validated['status'];
        }

        $template->save();

        return response()->json([
            'success' => true,
            'message' => "Template '{$template->title}' updated successfully!",
            'template' => $template,
        ]);
    }

    /**
     * Send a live test email to any inbox.
     */
    public function sendTest(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'type' => 'required|in:template,mailable',
            'id' => 'required',
        ]);

        $recipient = $request->input('email');
        $type = $request->input('type');
        $id = $request->input('id');

        $customData = $request->input('data');
        if (is_string($customData)) {
            $decoded = json_decode($customData, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $customData = $decoded;
            }
        }
        $customData = is_array($customData) ? $customData : [];

        try {
            if ($type === 'template') {
                $template = EmailTemplate::find($id);
                if (!$template) {
                    $template = EmailTemplate::where('slug', $id)->first();
                }
                if (!$template) {
                    return response()->json(['success' => false, 'message' => 'Template not found.'], 404);
                }

                $meta = EmailPreviewSampleDataProvider::getDynamicTemplateData($template->slug);
                $replacements = array_merge($meta['replacements'] ?? [], $customData);

                $mailable = new DynamicEmail($template, $replacements);
                Mail::to($recipient)->send($mailable);

                $subject = $mailable->dynamicSubject;
            } else {
                $mailables = EmailPreviewSampleDataProvider::getMailableRegistry();
                if (!isset($mailables[$id])) {
                    return response()->json(['success' => false, 'message' => 'Mailable not found.'], 404);
                }

                $meta = $mailables[$id];
                $data = array_merge($meta['sample_data'] ?? [], $customData);
                $factory = $meta['factory'];

                $mailable = $factory($data);

                if (is_object($mailable) && $mailable instanceof \Illuminate\Mail\Mailable) {
                    Mail::to($recipient)->send($mailable);
                    $subject = $meta['title'];
                } elseif (is_string($mailable)) {
                    // Send raw HTML view
                    Mail::html($mailable, function ($message) use ($recipient, $meta) {
                        $message->to($recipient)
                                ->subject('[Test] ' . $meta['title']);
                    });
                    $subject = $meta['title'];
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Test email successfully sent to {$recipient}!",
                'subject' => $subject ?? '',
            ]);
        } catch (\Throwable $e) {
            Log::error('Send test email failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage(),
            ], 500);
        }
    }
}
