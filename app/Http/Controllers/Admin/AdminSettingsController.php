<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AdminSettingsController extends Controller
{
    // ── General Settings ─────────────────────────────────────────────────────

    public function general()
    {
        $keys = [
            'site_name','site_url','site_description','site_keywords',
            'location','copyright','admin_email',
            'favicon','logo1','logo2',
            'email_verification','otp_verification',
            'theme_color','app_version',
            'support_whatsapp','support_phone','support_email','support_hours','support_ticket_url',
        ];
        $s = AppSetting::getMany($keys);
        return view('admin.settings.general', compact('s'));
    }

    public function updateGeneral(Request $request)
    {
        $textFields = [
            'site_name','site_url','site_description','site_keywords',
            'location','copyright','admin_email',
            'email_verification','otp_verification',
            'theme_color','app_version',
            'support_whatsapp','support_phone','support_email','support_hours','support_ticket_url',
        ];

        foreach ($textFields as $key) {
            AppSetting::set($key, $request->input($key, ''));
        }

        foreach (['favicon', 'logo1', 'logo2'] as $field) {
            if ($request->hasFile($field) && $request->file($field)->isValid()) {
                // Delete old file
                $old = AppSetting::get($field);
                if ($old && Storage::disk('public')->exists($old)) {
                    Storage::disk('public')->delete($old);
                }
                $path = $request->file($field)->store('settings', 'public');
                AppSetting::set($field, $path);
            }
        }

        return back()->with('success', 'General settings saved successfully.');
    }

    // ── Email Settings ────────────────────────────────────────────────────────

    public function email()
    {
        $keys = ['mail_host','mail_username','mail_password','mail_port','mail_from_address','mail_reply_to'];
        $s = AppSetting::getMany($keys);
        return view('admin.settings.email', compact('s'));
    }

    public function updateEmail(Request $request)
    {
        $fields = ['mail_host','mail_username','mail_password','mail_port','mail_from_address','mail_reply_to'];
        foreach ($fields as $key) {
            AppSetting::set($key, $request->input($key, ''));
        }
        return back()->with('success', 'Email settings saved successfully.');
    }

    public function sendTestEmail(Request $request)
    {
        $to = AppSetting::get('admin_email') ?: auth()->user()->email;

        // Dynamically apply saved SMTP settings
        config([
            'mail.mailers.smtp.host'       => AppSetting::get('mail_host', config('mail.mailers.smtp.host')),
            'mail.mailers.smtp.port'       => AppSetting::get('mail_port', config('mail.mailers.smtp.port')),
            'mail.mailers.smtp.username'   => AppSetting::get('mail_username', config('mail.mailers.smtp.username')),
            'mail.mailers.smtp.password'   => AppSetting::get('mail_password', config('mail.mailers.smtp.password')),
            'mail.from.address'            => AppSetting::get('mail_from_address', config('mail.from.address')),
            'mail.from.name'               => AppSetting::get('site_name', config('mail.from.name')),
        ]);

        try {
            Mail::raw('This is a test email from ' . AppSetting::get('site_name', 'PayPulse') . '. Your email configuration is working correctly.', function ($msg) use ($to) {
                $msg->to($to)->subject('Test Email - ' . AppSetting::get('site_name', 'PayPulse'));
            });
            return back()->with('success', 'Test email sent to ' . $to);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send test email: ' . $e->getMessage());
        }
    }

    // ── API Keys Settings ─────────────────────────────────────────────────────

    public function apiKeys()
    {
        $keys = [
            'flutterwave_public_key','flutterwave_secret_key','flutterwave_encryption_key','flutterwave_bvn',
            'paystack_public_key','paystack_secret_key',
            'payvessel_api_key','payvessel_secret_key','payvessel_business_id',
            'monnify_secret_key','monnify_contract_no','monnify_api',
            'payscribe_secret_key','payscribe_public_key',
            'tx_charge_m2m','tx_charge_bank',
            'vtpass_username','vtpass_password','vtpass_api_key',
            'primebiller_api_key','primebiller_status',
            'aabaxztech_username','aabaxztech_password','aabaxztech_api_key',
            'autopilot_email','autopilot_api_key',
            'easyaccess_api_key',
            'legitdataway_username','legitdataway_password','legitdataway_api_key',
            'merrybills_username','merrybills_password','merrybills_pin','merrybills_token',
            'clubkonnect_user_id','clubkonnect_api_key',
            'globacom_xapi_key','globacom_sponsor_id',
            'qoreid_client_key','qoreid_secret_key',
            'termii_api_key',
            'bulksms_sender','bulksms_api_key','bulksms_amount_per_unit',
            'openweather_api_key',
            'onesignal_app_id','onesignal_api_key',
            'airtime2cash_phone','airtime2cash_tx_charge','airtime2cash_max_per_payment','airtime2cash_min_per_payment',
            'referral_commission','referral_min_withdrawal','referral_min_total_spent',
        ];
        $s = AppSetting::getMany($keys);
        return view('admin.settings.api-keys', compact('s'));
    }

    public function updateApiKeys(Request $request)
    {
        $passwordFields = ['vtpass_password','aabaxztech_password','legitdataway_password','merrybills_password'];
        $data = $request->except(['_token','_method']);
        foreach ($data as $key => $value) {
            if (in_array($key, $passwordFields) && $value === '') {
                continue; // don't wipe existing passwords
            }
            AppSetting::set($key, $value ?? '');
        }
        return back()->with('success', 'API keys saved successfully.');
    }

    // ── API Settings ──────────────────────────────────────────────────────────

    public function api()
    {
        $keys = [
            'data_api_mtn','data_api_airtel','data_api_glo','data_api_etisalat',
            'mtn_sme','mtn_gifting','mtn_sme2','mtn_awoof','mtn_corporate_gifting',
            'airtel_gifting','airtel_corporate_gifting','airtel_awoof',
            'glo_awoof','glo_corporate_gifting',
            'etisalat_sme','etisalat_gifting',
            'airtime_net_mtn','airtime_net_airtel','airtime_net_glo','airtime_net_etisalat',
            'airtime_api','datacard_api','airtime_pin_api',
            'epins_api','electricity_api','cable_api','betting_api',
            'dealing_charge',
            'normal_airtime_mtn','normal_airtime_airtel','normal_airtime_glo','normal_airtime_etisalat',
            'agent_airtime_mtn','agent_airtime_airtel','agent_airtime_glo','agent_airtime_etisalat',
            'normal_pin_mtn','normal_pin_airtel','normal_pin_glo','normal_pin_etisalat',
            'agent_pin_mtn','agent_pin_airtel','agent_pin_glo','agent_pin_etisalat',
            // service enable/disable toggles
            'data_service_status','airtime_service_status','electricity_service_status',
            'cable_service_status','epins_service_status','betting_service_status',
        ];
        $s = AppSetting::getMany($keys);

        // Only surface providers whose credentials have been configured
        $providerCredentialMap = [
            'vtpass'       => 'vtpass_api_key',
            'easyaccess'   => 'easyaccess_api_key',
            'primebiller'  => 'primebiller_api_key',
            'payscribe'    => 'payscribe_secret_key',
            'merrybills'   => 'merrybills_token',
            'clubkonnect'  => 'clubkonnect_api_key',
            'autopilot'    => 'autopilot_api_key',
            'aabaxztech'   => 'aabaxztech_api_key',
            'legitdataway' => 'legitdataway_api_key',
            'globacom'    => 'globacom_xapi_key',
        ];
        $credValues = AppSetting::getMany(array_values($providerCredentialMap));
        $availableProviders = [];
        foreach ($providerCredentialMap as $name => $credKey) {
            if (!empty($credValues[$credKey])) {
                $availableProviders[] = $name;
            }
        }

        return view('admin.settings.api', compact('s', 'availableProviders'));
    }

    public function updateApi(Request $request)
    {
        $data = $request->except(['_token','_method']);
        foreach ($data as $key => $value) {
            AppSetting::set($key, $value ?? '');
        }
        return back()->with('success', 'API settings saved successfully.');
    }

    // ── Account Settings ──────────────────────────────────────────────────────

    public function accounts()
    {
        $accounts = \App\Models\BankAccount::orderBy('id')->get();
        return view('admin.settings.accounts', compact('accounts'));
    }

    public function storeAccount(Request $request)
    {
        $request->validate([
            'bank_name'      => 'required|string|max:100',
            'account_number' => 'required|string|max:20',
            'account_name'   => 'required|string|max:100',
            'short_code'     => 'nullable|string|max:20',
        ]);
        \App\Models\BankAccount::create($request->only(['bank_name','account_number','account_name','short_code']));
        return back()->with('success', 'Account added successfully.');
    }

    public function updateAccount(Request $request, $id)
    {
        $account = \App\Models\BankAccount::findOrFail($id);
        $request->validate([
            'bank_name'      => 'required|string|max:100',
            'account_number' => 'required|string|max:20',
            'account_name'   => 'required|string|max:100',
            'short_code'     => 'nullable|string|max:20',
        ]);
        $account->update($request->only(['bank_name','account_number','account_name','short_code']));
        return back()->with('success', 'Account updated successfully.');
    }

    public function destroyAccount($id)
    {
        \App\Models\BankAccount::findOrFail($id)->delete();
        return back()->with('success', 'Account deleted.');
    }

    // ── Cable Plan Settings ───────────────────────────────────────────────────

    public function cablePlans()
    {
        $providers = \App\Models\CableProvider::orderBy('sort_order')->get();
        $plans     = \App\Models\CablePlan::with('provider')
                        ->orderBy('cable_provider_id')->orderBy('sort_order')->get();
        return view('admin.settings.cable-plans', compact('plans', 'providers'));
    }

    public function storeCablePlan(Request $request)
    {
        $request->validate([
            'cable_provider_id' => 'required|exists:cable_providers,id',
            'name'              => 'required|string|max:100',
            'vtpass_id'         => 'required|string|max:100',
            'easyaccess_id'     => 'nullable|string|max:100',
            'payscribe_id'      => 'nullable|string|max:255',
            'amount'            => 'required|numeric|min:0',
            'sort_order'        => 'nullable|integer|min:0',
        ]);
        \App\Models\CablePlan::create([
            'cable_provider_id' => $request->cable_provider_id,
            'name'              => $request->name,
            'vtpass_id'         => $request->vtpass_id,
            'easyaccess_id'     => $request->easyaccess_id,
            'payscribe_id'      => $request->payscribe_id,
            'amount'            => $request->amount,
            'enabled'           => $request->has('enabled') ? (bool)$request->enabled : true,
            'sort_order'        => $request->sort_order ?? 0,
        ]);
        return back()->with('success', 'Cable plan added.');
    }

    public function updateCablePlan(Request $request, $id)
    {
        $plan = \App\Models\CablePlan::findOrFail($id);
        $request->validate([
            'name'      => 'required|string|max:100',
            'vtpass_id' => 'required|string|max:100',
            'amount'    => 'required|numeric|min:0',
        ]);
        $plan->update([
            'cable_provider_id' => $request->cable_provider_id ?? $plan->cable_provider_id,
            'name'              => $request->name,
            'vtpass_id'         => $request->vtpass_id,
            'easyaccess_id'     => $request->easyaccess_id,
            'payscribe_id'      => $request->payscribe_id,
            'amount'            => $request->amount,
            'enabled'           => $request->has('enabled') ? (bool)$request->enabled : $plan->enabled,
            'sort_order'        => $request->sort_order ?? $plan->sort_order,
        ]);
        return back()->with('success', 'Cable plan updated.');
    }

    public function destroyCablePlan($id)
    {
        \App\Models\CablePlan::findOrFail($id)->delete();
        return back()->with('success', 'Cable plan deleted.');
    }

    // ── Data Plan Settings ────────────────────────────────────────────────────

    public function dataPlans($network = 'mtn')
    {
        $networks    = ['mtn' => 'MTN', 'airtel' => 'Airtel', 'glo' => 'Glo', 'etisalat' => '9Mobile'];
        $network     = array_key_exists($network, $networks) ? $network : 'mtn';
        $plans       = \App\Models\DataPlan::where('network_key', $network)
                           ->orderBy('data_type')->orderBy('sort_order')->get();
        $dataTypes   = $plans->pluck('data_type')->unique()->sort()->values();
        return view('admin.settings.data-plans', compact('plans', 'networks', 'network', 'dataTypes'));
    }

    public function storeDataPlan(Request $request)
    {
        $request->validate([
            'network_key'           => 'required|in:mtn,airtel,glo,etisalat',
            'data_type'             => 'required|string|max:50',
            'plan_name'             => 'required|string|max:100',
            'validity'              => 'nullable|string|max:50',
            'vtpass_id'             => 'nullable|string|max:100',
            'clubkonnect_id'        => 'nullable|string|max:100',
            'easyaccess_id'         => 'nullable|string|max:100',
            'aabaxztech_id'         => 'nullable|string|max:100',
            'legitdataway_id'       => 'nullable|string|max:100',
            'globacom_id'           => 'nullable|string|max:100',
            'autopilot_id'          => 'nullable|string|max:100',
            'merrybills_product_id' => 'nullable|string|max:100',
            'merrybills_id'         => 'nullable|string|max:100',
            'amount'                => 'required|numeric|min:0',
            'amount_agent'          => 'nullable|numeric|min:0',
            'sort_order'            => 'nullable|integer|min:0',
        ]);
        \App\Models\DataPlan::create(array_merge(
            $request->only([
                'network_key','data_type','plan_name','validity',
                'vtpass_id','clubkonnect_id','easyaccess_id','aabaxztech_id',
                'legitdataway_id','globacom_id','autopilot_id',
                'merrybills_product_id','merrybills_id',
                'amount','amount_agent','sort_order',
            ]),
            ['enabled' => $request->boolean('enabled', true)]
        ));
        return back()->with('success', 'Data plan added.');
    }

    public function updateDataPlan(Request $request, $id)
    {
        $plan = \App\Models\DataPlan::findOrFail($id);
        $plan->update(array_merge(
            $request->only([
                'network_key','data_type','plan_name','validity',
                'vtpass_id','clubkonnect_id','easyaccess_id','aabaxztech_id',
                'legitdataway_id','globacom_id','autopilot_id',
                'merrybills_product_id','merrybills_id',
                'amount','amount_agent','sort_order',
            ]),
            ['enabled' => $request->boolean('enabled', false)]
        ));
        return back()->with('success', 'Data plan updated.');
    }

    public function destroyDataPlan($id)
    {
        \App\Models\DataPlan::findOrFail($id)->delete();
        return back()->with('success', 'Data plan deleted.');
    }
}
