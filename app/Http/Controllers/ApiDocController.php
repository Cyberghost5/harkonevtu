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

        return view('api-docs', compact('baseUrl', 'siteName'));
    }
}
