<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(): View
    {
        $s = AppSetting::getMany([
            'support_whatsapp',
            'support_phone',
            'support_email',
            'support_hours',
            'support_ticket_url',
            'site_name',
            'admin_email',
        ]);

        // Fallback: use admin_email if no dedicated support_email set
        $s['support_email'] = $s['support_email'] ?: $s['admin_email'] ?? '';

        return view('support.index', compact('s'));
    }

    public function send(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $adminEmail = AppSetting::get('support_email') ?: AppSetting::get('admin_email', config('mail.from.address'));

        if ($adminEmail) {
            try {
                \Illuminate\Support\Facades\Mail::raw("New Contact Form Inquiry from " . $request->name . " (" . $request->email . "):\n\n" . $request->message, function ($msg) use ($adminEmail, $request) {
                    $msg->to($adminEmail)
                        ->replyTo($request->email)
                        ->subject('New Contact Form Submission - ' . $request->name);
                });
            } catch (\Exception $e) {
                // Ignore mail sending exceptions
            }
        }

        return back()->with('success', 'Thank you for contacting us! Your message has been sent successfully.');
    }
}
