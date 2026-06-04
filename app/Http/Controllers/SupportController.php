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
}
