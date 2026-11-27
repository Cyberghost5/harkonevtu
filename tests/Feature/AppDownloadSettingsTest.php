<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppDownloadSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders_with_app_download_links(): void
    {
        AppSetting::set('play_store_url', 'https://play.google.com/store/apps/details?id=com.dakeventures.app');
        AppSetting::set('app_store_url', 'https://apps.apple.com/app/dakeventures/id123456');
        AppSetting::set('apk_download_url', 'https://dakeventures.com.ng/download/app.apk');

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Download our App now');
        $response->assertSee('https://play.google.com/store/apps/details?id=com.dakeventures.app');
        $response->assertSee('https://apps.apple.com/app/dakeventures/id123456');
        $response->assertSee('https://dakeventures.com.ng/download/app.apk');
    }

    public function test_download_page_renders_successfully(): void
    {
        $response = $this->get('/download');
        $response->assertStatus(200);
        $response->assertSee('Official Mobile App');
        $response->assertSee('Google Play Store');
        $response->assertSee('Apple App Store');
        $response->assertSee('Direct APK Download');
    }

    public function test_app_config_api_returns_download_urls(): void
    {
        AppSetting::set('play_store_url', 'https://play.google.com/store/apps/details?id=com.test.vtu');
        AppSetting::set('app_store_url', 'https://apps.apple.com/app/test/id999');
        AppSetting::set('apk_download_url', 'https://test.vtu/test.apk');

        $response = $this->getJson('/api/v1/app-config');
        $response->assertStatus(200);
        $response->assertJsonPath('data.play_store_url', 'https://play.google.com/store/apps/details?id=com.test.vtu');
        $response->assertJsonPath('data.app_store_url', 'https://apps.apple.com/app/test/id999');
        $response->assertJsonPath('data.apk_download_url', 'https://test.vtu/test.apk');
    }

    public function test_admin_can_save_app_download_links(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post('/admin/settings/general', [
            'site_name' => 'DakeVentures',
            'play_store_url' => 'https://play.google.com/store/apps/details?id=com.dakeventures',
            'app_store_url' => 'https://apps.apple.com/app/dakeventures/id111222',
            'apk_download_url' => 'https://dakeventures.com.ng/dake.apk',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('https://play.google.com/store/apps/details?id=com.dakeventures', AppSetting::get('play_store_url'));
        $this->assertEquals('https://apps.apple.com/app/dakeventures/id111222', AppSetting::get('app_store_url'));
        $this->assertEquals('https://dakeventures.com.ng/dake.apk', AppSetting::get('apk_download_url'));
    }
}
