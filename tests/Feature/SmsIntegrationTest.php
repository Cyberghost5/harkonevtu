<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SmsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::set('email_verification', '0');
        AppSetting::set('otp_verification', '1');
    }

    public function test_send_otp_via_termii_succeeds(): void
    {
        AppSetting::set('termii_api_key', 'mock_termii_key');
        AppSetting::set('bulksms_sender', 'MyBrand');
        AppSetting::set('site_name', 'PayPulse');

        $user = User::factory()->create([
            'phone' => '08031234567',
            'phone_verified_at' => null,
            'transaction_pin' => bcrypt('1234'),
        ]);

        Http::fake([
            'https://api.ng.termii.com/api/sms/send' => Http::response([
                'message' => 'Successfully Sent',
                'code' => 'ok',
            ], 200),
        ]);

        $this->actingAs($user);
        $response = $this->from(route('verification.phone'))->post(route('verification.phone.send'));
        $response->assertRedirect(route('verification.phone'));
        $response->assertSessionHas('success', 'A new OTP has been sent to ' . $user->phone);

        // Verify the code was cached
        $cachedOtp = Cache::get('phone_otp_' . $user->id);
        $this->assertNotNull($cachedOtp);

        // Verify Termii HTTP request payload
        Http::assertSent(function ($request) use ($user, $cachedOtp) {
            return $request->url() === 'https://api.ng.termii.com/api/sms/send' &&
                $request['api_key'] === 'mock_termii_key' &&
                $request['to'] === '2348031234567' &&
                $request['from'] === 'MyBrand' &&
                str_contains($request['sms'], "verification code is: " . $cachedOtp);
        });
    }

    public function test_send_otp_falls_back_to_bulksms(): void
    {
        // Set bulksms but NOT termii
        AppSetting::set('termii_api_key', '');
        AppSetting::set('bulksms_api_key', 'mock_bulksms_key');
        AppSetting::set('bulksms_sender', 'MyBrand');

        $user = User::factory()->create([
            'phone' => '08031234567',
            'phone_verified_at' => null,
            'transaction_pin' => bcrypt('1234'),
        ]);

        Http::fake([
            'https://www.bulksmsnigeria.com/api/v2/sms' => Http::response([
                'status' => 'success',
            ], 200),
        ]);

        $this->actingAs($user);
        $response = $this->from(route('verification.phone'))->post(route('verification.phone.send'));
        $response->assertRedirect(route('verification.phone'));

        $cachedOtp = Cache::get('phone_otp_' . $user->id);

        // Verify BulkSMS HTTP request was sent
        Http::assertSent(function ($request) use ($user, $cachedOtp) {
            return $request->url() === 'https://www.bulksmsnigeria.com/api/v2/sms' &&
                $request->hasHeader('Authorization', 'Bearer mock_bulksms_key') &&
                $request['to'] === '2348031234567' &&
                $request['from'] === 'MyBrand' &&
                str_contains($request['body'], "verification code is: " . $cachedOtp);
        });
    }
}
