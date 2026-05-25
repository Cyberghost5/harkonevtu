<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
    ],

    'flutterwave' => [
        'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
        'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
        'hash'       => env('FLUTTERWAVE_HASH'),      // webhook verification hash
    ],

    'vtpass' => [
        'api_key'    => env('VTPASS_API_KEY'),
        'public_key' => env('VTPASS_PUBLIC_KEY'),
        'secret_key' => env('VTPASS_SECRET_KEY'),
        'base_url'   => env('VTPASS_BASE_URL', 'https://sandbox.vtpass.com'), // prod: https://vtpass.com
    ],

    'clubkonnect' => [
        'user_id' => env('CLUBKONNECT_USER_ID'),
        'api_key' => env('CLUBKONNECT_API_KEY'),
    ],

    'autopilot' => [
        'api_key'  => env('AUTOPILOT_API_KEY'),
        'base_url' => env('AUTOPILOT_BASE_URL', 'https://autopilotng.com/api'),
    ],

    'merrybills' => [
        'token'   => env('MERRYBILLS_TOKEN'),
        'pin'     => env('MERRYBILLS_PIN'),
        'base_url'=> env('MERRYBILLS_BASE_URL', 'https://merrybills.com'),
    ],

    'easyaccess' => [
        'token' => env('EASYACCESS_TOKEN'),
        'base_url' => env('EASYACCESS_BASE_URL', 'https://easyaccess.com/api'),
    ],

    'aabaxztech' => [
        'token' => env('AABAXZTECH_TOKEN'),
        'base_url' => env('AABAXZTECH_BASE_URL', 'https://aabaxztech.com/api'),
    ],

    'legitdataway' => [
        'token'    => env('LEGITDATAWAY_TOKEN'),
        'base_url' => env('LEGITDATAWAY_BASE_URL', 'https://legitdataway.com/api'),
    ],

    'globacom' => [
        'x_api_key'  => env('GLOBACOM_X_API_KEY'),
        'sponsor_id' => env('GLOBACOM_SPONSOR_ID', 'klasspay'),
        'bucket_id'  => env('GLOBACOM_BUCKET_ID'),
        'base_url'   => env('GLOBACOM_BASE_URL', 'https://gift-api.gloworld.com'),
    ],

    'payscribe' => [
        'public_key' => env('PAYSCRIBE_PUBLIC_KEY'),
        'secret_key' => env('PAYSCRIBE_SECRET_KEY'),
        'base_url'   => env('PAYSCRIBE_BASE_URL', 'https://api.payscribe.ng/api/v1'),
    ],

    'primebiller' => [
        'token'    => env('PRIMEBILLER_TOKEN'),
        'base_url' => env('PRIMEBILLER_BASE_URL', 'https://primebiller.com/api/v1'),
    ],

];
