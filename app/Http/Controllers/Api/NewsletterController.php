<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use Illuminate\Http\Request;
use App\Mail\DynamicEmail;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    /**
     * Subscribe to the newsletter.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = $request->input('email');

        $newsletter = Newsletter::where('email', $email)->first();

        if ($newsletter) {
            if (!$newsletter->is_active) {
                $newsletter->update(['is_active' => true]);
            } else {
                return response()->json([
                    'status' => 'info',
                    'message' => 'You are already subscribed to the newsletter.'
                ], 200);
            }
        } else {
            $newsletter = Newsletter::create([
                'email' => $email,
                'is_active' => true,
            ]);
        }

        // Send the subscription email
        try {
            $template = \App\Models\EmailTemplate::where('slug', 'Newsletter_Subscription')->first();
            if ($template) {
                Mail::to($email)->send(new DynamicEmail($template, []));
            }
        } catch (\Exception $e) {
            \Log::error('Newsletter subscription email failed: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully subscribed to the newsletter.'
        ], 200);
    }
}
