<?php

namespace App\Providers;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        try {
            if (Schema::hasTable('app_settings')) {
                $s = AppSetting::getMany([
                    'site_name', 'site_description', 'site_keywords', 'admin_email',
                    'copyright', 'location', 'favicon', 'logo1', 'logo2', 'theme_color',
                    'easyaccess_api_key',
                    'vtpass_api_key', 'vtpass_public_key', 'vtpass_secret_key',
                    'clubkonnect_api_key', 'clubkonnect_user_id',
                    'autopilot_api_key',
                    'merrybills_token', 'merrybills_pin',
                    'aabaxztech_api_key',
                    'legitdataway_api_key',
                    'globacom_xapi_key', 'globacom_sponsor_id', 'globacom_bucket_id',
                    'payscribe_secret_key', 'payscribe_public_key',
                    'primebiller_api_key',
                    'paystack_secret_key', 'paystack_public_key',
                    'flutterwave_secret_key', 'flutterwave_public_key', 'flutterwave_encryption_key', 'flutterwave_hash',
                    'mail_host', 'mail_username', 'mail_password', 'mail_port', 'mail_from_address', 'mail_reply_to',
                ]);

                // Map database settings to dynamic configuration overrides
                config([
                    'services.easyaccess.token' => !empty($s['easyaccess_api_key']) ? $s['easyaccess_api_key'] : config('services.easyaccess.token'),
                    'services.vtpass.api_key' => !empty($s['vtpass_api_key']) ? $s['vtpass_api_key'] : config('services.vtpass.api_key'),
                    'services.vtpass.public_key' => !empty($s['vtpass_public_key']) ? $s['vtpass_public_key'] : config('services.vtpass.public_key'),
                    'services.vtpass.secret_key' => !empty($s['vtpass_secret_key']) ? $s['vtpass_secret_key'] : config('services.vtpass.secret_key'),
                    'services.clubkonnect.api_key' => !empty($s['clubkonnect_api_key']) ? $s['clubkonnect_api_key'] : config('services.clubkonnect.api_key'),
                    'services.clubkonnect.user_id' => !empty($s['clubkonnect_user_id']) ? $s['clubkonnect_user_id'] : config('services.clubkonnect.user_id'),
                    'services.autopilot.api_key' => !empty($s['autopilot_api_key']) ? $s['autopilot_api_key'] : config('services.autopilot.api_key'),
                    'services.merrybills.token' => !empty($s['merrybills_token']) ? $s['merrybills_token'] : config('services.merrybills.token'),
                    'services.merrybills.pin' => !empty($s['merrybills_pin']) ? $s['merrybills_pin'] : config('services.merrybills.pin'),
                    'services.aabaxztech.token' => !empty($s['aabaxztech_api_key']) ? $s['aabaxztech_api_key'] : config('services.aabaxztech.token'),
                    'services.legitdataway.token' => !empty($s['legitdataway_api_key']) ? $s['legitdataway_api_key'] : config('services.legitdataway.token'),
                    'services.globacom.x_api_key' => !empty($s['globacom_xapi_key']) ? $s['globacom_xapi_key'] : config('services.globacom.x_api_key'),
                    'services.globacom.sponsor_id' => !empty($s['globacom_sponsor_id']) ? $s['globacom_sponsor_id'] : config('services.globacom.sponsor_id'),
                    'services.globacom.bucket_id' => !empty($s['globacom_bucket_id']) ? $s['globacom_bucket_id'] : config('services.globacom.bucket_id'),
                    'services.payscribe.secret_key' => !empty($s['payscribe_secret_key']) ? $s['payscribe_secret_key'] : config('services.payscribe.secret_key'),
                    'services.payscribe.public_key' => !empty($s['payscribe_public_key']) ? $s['payscribe_public_key'] : config('services.payscribe.public_key'),
                    'services.primebiller.token' => !empty($s['primebiller_api_key']) ? $s['primebiller_api_key'] : config('services.primebiller.token'),
                    'services.paystack.secret_key' => !empty($s['paystack_secret_key']) ? $s['paystack_secret_key'] : config('services.paystack.secret_key'),
                    'services.paystack.public_key' => !empty($s['paystack_public_key']) ? $s['paystack_public_key'] : config('services.paystack.public_key'),
                    'services.flutterwave.secret_key' => !empty($s['flutterwave_secret_key']) ? $s['flutterwave_secret_key'] : config('services.flutterwave.secret_key'),
                    'services.flutterwave.public_key' => !empty($s['flutterwave_public_key']) ? $s['flutterwave_public_key'] : config('services.flutterwave.public_key'),
                    'services.flutterwave.encryption_key' => !empty($s['flutterwave_encryption_key']) ? $s['flutterwave_encryption_key'] : config('services.flutterwave.encryption_key'),
                    'services.flutterwave.hash' => !empty($s['flutterwave_hash']) ? $s['flutterwave_hash'] : config('services.flutterwave.hash'),
                ]);

                // Map SMTP mailer configuration dynamically
                if (!empty($s['mail_host'])) {
                    $port = (int) ($s['mail_port'] ?: 587);
                    $scheme = null;
                    if ($port === 465) {
                        $scheme = 'smtps';
                    } elseif ($port === 587) {
                        $scheme = 'tls';
                    }

                    config([
                        'mail.default' => 'smtp',
                        'mail.mailers.smtp.host' => $s['mail_host'],
                        'mail.mailers.smtp.port' => $port,
                        'mail.mailers.smtp.username' => $s['mail_username'],
                        'mail.mailers.smtp.password' => $s['mail_password'],
                        'mail.mailers.smtp.scheme' => $scheme,
                        'mail.from.address' => $s['mail_from_address'] ?: config('mail.from.address'),
                        'mail.from.name' => $s['site_name'] ?: config('mail.from.name'),
                    ]);
                }

                $colors = $this->deriveThemeColors($s['theme_color'] ?: '#4caf50');
                $this->shareAll($s, $colors);
            } else {
                $this->shareAll([], $this->deriveThemeColors('#4caf50'));
            }
        } catch (\Throwable $e) {
            $this->shareAll([], $this->deriveThemeColors('#4caf50'));
        }
    }

    private function shareAll(array $s, array $c): void
    {
        View::share('siteName',           $s['site_name']        ?? config('app.name', 'PayPulse') ?: config('app.name', 'PayPulse'));
        View::share('siteDescription',    $s['site_description'] ?? '');
        View::share('siteKeywords',       $s['site_keywords']    ?? '');
        View::share('adminEmail',         $s['admin_email']      ?? '');
        View::share('siteCopyright',      $s['copyright']        ?? '');
        View::share('siteLocation',       $s['location']         ?? '');
        View::share('siteFavicon',        $s['favicon']          ?? '');
        View::share('siteLogo1',          $s['logo1']            ?? '');
        View::share('siteLogo2',          $s['logo2']            ?? '');
        View::share('themeColor',         $c['primary']);
        View::share('themeSecondary',     $c['secondary']);
        View::share('themeDark',          $c['dark']);
        View::share('themeColorRgb',      $c['primaryRgb']);
        View::share('themeSecondaryRgb',  $c['secondaryRgb']);
    }

    // ── Color Derivation ──────────────────────────────────────────────────────

    private function deriveThemeColors(string $input): array
    { 
        $hex = preg_replace('/[^0-9a-fA-F]/', '', $input);
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6) {
            $hex = '4caf50';
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        [$h, $s, $l] = $this->rgbToHsl($r, $g, $b);

        // Clamp primary to a vibrant, mid-range lightness
        $ps = max($s, 0.42);
        $pl = max(0.36, min($l, 0.64));
        $primaryHex = $this->hslToHex($h, $ps, $pl);

        // Secondary: shift hue +40°, lighter, less saturated (nice gradient end)
        $secondaryHex = $this->hslToHex(
            fmod($h + 0.111, 1.0),
            max($ps * 0.65, 0.30),
            min($pl + 0.22, 0.78)
        );

        // Dark: same hue, slightly more saturated, very low lightness (sidebar bg)
        $darkHex = $this->hslToHex(
            $h,
            min($ps + 0.10, 0.85),
            max($pl * 0.32, 0.08)
        );

        return [
            'primary'      => '#' . $primaryHex,
            'secondary'    => '#' . $secondaryHex,
            'dark'         => '#' . $darkHex,
            'primaryRgb'   => implode(', ', array_map('hexdec', str_split($primaryHex, 2))),
            'secondaryRgb' => implode(', ', array_map('hexdec', str_split($secondaryHex, 2))),
        ];
    }

    private function rgbToHsl(float $r, float $g, float $b): array
    {
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l   = ($max + $min) / 2.0;

        if ($max === $min) {
            return [0.0, 0.0, $l];
        }

        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2.0 - $max - $min) : $d / ($max + $min);
        $h = match (true) {
            $max === $r => (($g - $b) / $d + ($g < $b ? 6.0 : 0.0)) / 6.0,
            $max === $g => (($b - $r) / $d + 2.0) / 6.0,
            default     => (($r - $g) / $d + 4.0) / 6.0,
        };

        return [$h, $s, $l];
    }

    private function hslToHex(float $h, float $s, float $l): string
    {
        if ($s === 0.0) {
            $v = (int) round($l * 255);
            return sprintf('%02x%02x%02x', $v, $v, $v);
        }

        $q = $l < 0.5 ? $l * (1.0 + $s) : $l + $s - $l * $s;
        $p = 2.0 * $l - $q;

        return sprintf(
            '%02x%02x%02x',
            (int) round($this->hue2rgb($p, $q, $h + 1 / 3) * 255),
            (int) round($this->hue2rgb($p, $q, $h)         * 255),
            (int) round($this->hue2rgb($p, $q, $h - 1 / 3) * 255)
        );
    }

    private function hue2rgb(float $p, float $q, float $t): float
    {
        if ($t < 0) $t += 1.0;
        if ($t > 1) $t -= 1.0;
        if ($t < 1 / 6) return $p + ($q - $p) * 6.0 * $t;
        if ($t < 1 / 2) return $q;
        if ($t < 2 / 3) return $p + ($q - $p) * (2 / 3 - $t) * 6.0;
        return $p;
    }
}
