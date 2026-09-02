<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;

class ApiDocController extends Controller
{
    public function index()
    {
        $baseUrl = url('/api/v1');
        $siteName = AppSetting::get('site_name', 'PayPulse');
        $postmanDownloadUrl = url('/api/docs/postman');

        return view('api-docs', compact('baseUrl', 'siteName', 'postmanDownloadUrl'));
    }

    public function exportPostman()
    {
        // Regenerate collection to ensure latest settings/URL
        \Illuminate\Support\Facades\Artisan::call('export:postman');

        $filePath = public_path('postman_collection.json');
        return response()->download($filePath, 'harkone_vtu_api_postman_collection.json', [
            'Content-Type' => 'application/json',
        ]);
    }
}
