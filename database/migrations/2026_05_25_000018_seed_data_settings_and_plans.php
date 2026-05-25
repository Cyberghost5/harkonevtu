<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── App Settings ──────────────────────────────────────────────────────
        $settings = [
            // Service toggle
            'service_data'                    => '1',
            // Per-network API selection (default: autopilot)
            'data_api_mtn'                    => 'autopilot',
            'data_api_glo'                    => 'autopilot',
            'data_api_airtel'                 => 'autopilot',
            'data_api_etisalat'               => 'autopilot',
            // Daily purchase limit
            'data_daily_limit'                => '100000',
            // Per-network per-type enable flags
            // MTN
            'data_type_mtn_sme'               => '1',
            'data_type_mtn_gifting'           => '1',
            'data_type_mtn_cg'                => '0',
            'data_type_mtn_awoof'             => '1',
            // Glo
            'data_type_glo_sme'               => '0',
            'data_type_glo_gifting'           => '0',
            'data_type_glo_cg'                => '1',
            'data_type_glo_awoof'             => '0',
            // Airtel
            'data_type_airtel_sme'            => '0',
            'data_type_airtel_gifting'        => '0',
            'data_type_airtel_cg'             => '1',
            'data_type_airtel_awoof'          => '1',
            // 9Mobile
            'data_type_etisalat_sme'          => '1',
            'data_type_etisalat_gifting'      => '0',
            'data_type_etisalat_cg'           => '1',
            'data_type_etisalat_awoof'        => '0',
            // Discount percentages
            'data_off_percentage_mtn'         => '0',
            'data_off_percentage_glo'         => '0',
            'data_off_percentage_airtel'      => '0',
            'data_off_percentage_etisalat'    => '0',
            'data_agent_off_percentage_mtn'   => '0',
            'data_agent_off_percentage_glo'   => '0',
            'data_agent_off_percentage_airtel' => '0',
            'data_agent_off_percentage_etisalat' => '0',
        ];

        foreach ($settings as $key => $value) {
            DB::table('app_settings')->updateOrInsert(['key' => $key], ['value' => $value]);
        }

        // ── Data Plans ────────────────────────────────────────────────────────
        $now = now();

        $plans = [
            // ══════════════ MTN SME ══════════════
            // Autopilot (SME short-validity & monthly plans)
            ['network_key' => 'mtn', 'data_type' => 'sme', 'plan_name' => '110MB Daily',     'validity' => '1 Day',   'autopilot_id' => 'MTN_DG_110MB_DAILY',       'amount' => 150,  'amount_agent' => 150,  'sort_order' => 1],
            ['network_key' => 'mtn', 'data_type' => 'sme', 'plan_name' => '230MB Daily',     'validity' => '1 Day',   'autopilot_id' => 'MTN_DG_230MB_DAILY',       'amount' => 230,  'amount_agent' => 230,  'sort_order' => 2],
            ['network_key' => 'mtn', 'data_type' => 'sme', 'plan_name' => '500MB',           'validity' => '7 Days',  'autopilot_id' => 'MTN_DG_500MB_7DAYS',       'amount' => 515,  'amount_agent' => 515,  'sort_order' => 3],
            ['network_key' => 'mtn', 'data_type' => 'sme', 'plan_name' => '1GB',             'validity' => '7 Days',  'autopilot_id' => 'MTN_DG_1GB_7DAYS',         'amount' => 810,  'amount_agent' => 810,  'sort_order' => 4],
            ['network_key' => 'mtn', 'data_type' => 'sme', 'plan_name' => '1.5GB',           'validity' => '7 Days',  'autopilot_id' => 'MTN_DG_1.5GB_7DAYS',       'amount' => 1050, 'amount_agent' => 1050, 'sort_order' => 5],
            ['network_key' => 'mtn', 'data_type' => 'sme', 'plan_name' => '2GB',             'validity' => '30 Days', 'autopilot_id' => 'MTN_DG_2GB_30DAYS',        'amount' => 1550, 'amount_agent' => 1550, 'sort_order' => 6],
            ['network_key' => 'mtn', 'data_type' => 'sme', 'plan_name' => '3.5GB',           'validity' => '30 Days', 'autopilot_id' => 'MTN_DG_3.5GB_30DAYS',      'amount' => 2550, 'amount_agent' => 2550, 'sort_order' => 7],
            ['network_key' => 'mtn', 'data_type' => 'sme', 'plan_name' => '7GB',             'validity' => '30 Days', 'autopilot_id' => 'MTN_DG_7GB_30DAYS',        'amount' => 3610, 'amount_agent' => 3610, 'sort_order' => 8],
            ['network_key' => 'mtn', 'data_type' => 'sme', 'plan_name' => '10GB',            'validity' => '30 Days', 'autopilot_id' => 'MTN_DG_10GB_30DAYS',       'amount' => 4715, 'amount_agent' => 4715, 'sort_order' => 9],
            ['network_key' => 'mtn', 'data_type' => 'sme', 'plan_name' => '12.5GB',          'validity' => '30 Days', 'autopilot_id' => 'MTN_DG_12.5GB_30DAYS',     'amount' => 5765, 'amount_agent' => 5765, 'sort_order' => 10],
            // Merrybills SME (cheaper, different catalogue)
            ['network_key' => 'mtn', 'data_type' => 'sme', 'plan_name' => '500MB',           'validity' => '30 Days', 'merrybills_product_id' => 'XIVns06YNF', 'merrybills_id' => '04', 'amount' => 335,  'amount_agent' => 332,  'sort_order' => 11],
            ['network_key' => 'mtn', 'data_type' => 'sme', 'plan_name' => '1GB',             'validity' => '30 Days', 'merrybills_product_id' => 'XIVns06YNF', 'merrybills_id' => '01', 'amount' => 669,  'amount_agent' => 665,  'sort_order' => 12],
            ['network_key' => 'mtn', 'data_type' => 'sme', 'plan_name' => '2GB',             'validity' => '30 Days', 'merrybills_product_id' => 'XIVns06YNF', 'merrybills_id' => '02', 'amount' => 1338, 'amount_agent' => 1330, 'sort_order' => 13],
            ['network_key' => 'mtn', 'data_type' => 'sme', 'plan_name' => '3GB',             'validity' => '30 Days', 'merrybills_product_id' => 'XIVns06YNF', 'merrybills_id' => '06', 'amount' => 2007, 'amount_agent' => 1995, 'sort_order' => 14],
            ['network_key' => 'mtn', 'data_type' => 'sme', 'plan_name' => '10GB',            'validity' => '30 Days', 'merrybills_product_id' => 'XIVns06YNF', 'merrybills_id' => '05', 'amount' => 6690, 'amount_agent' => 6650, 'sort_order' => 15],

            // ══════════════ MTN AWOOF ══════════════
            ['network_key' => 'mtn', 'data_type' => 'awoof', 'plan_name' => '75MB',          'validity' => '1 Day',   'autopilot_id' => 'MTN_DG_75MB_DAILY',                'amount' => 100,  'amount_agent' => 100,  'sort_order' => 1],
            ['network_key' => 'mtn', 'data_type' => 'awoof', 'plan_name' => '500MB',         'validity' => '1 Day',   'autopilot_id' => 'MTN_DG_500MB_DAILY',               'amount' => 360,  'amount_agent' => 360,  'sort_order' => 2],
            ['network_key' => 'mtn', 'data_type' => 'awoof', 'plan_name' => '750MB',         'validity' => '3 Days',  'autopilot_id' => 'MTN_DG_750MB_3DAYS_FREE_1HR_Y_I_T','amount' => 515,  'amount_agent' => 515,  'sort_order' => 3],
            ['network_key' => 'mtn', 'data_type' => 'awoof', 'plan_name' => '1GB',           'validity' => '1 Day',   'autopilot_id' => 'MTN_DG_1GB_DAILY',                 'amount' => 515,  'amount_agent' => 515,  'sort_order' => 4],
            ['network_key' => 'mtn', 'data_type' => 'awoof', 'plan_name' => '1.2GB Social',  'validity' => '30 Days', 'autopilot_id' => 'MTN_DG_1.2GB_SOCIAL_MONTHLY',     'amount' => 600,  'amount_agent' => 600,  'sort_order' => 5],
            ['network_key' => 'mtn', 'data_type' => 'awoof', 'plan_name' => '1.2GB Pulse',   'validity' => '7 Days',  'autopilot_id' => 'MTN_DG_1.2GB_7DAYS',              'amount' => 800,  'amount_agent' => 800,  'sort_order' => 6],
            ['network_key' => 'mtn', 'data_type' => 'awoof', 'plan_name' => '1.5GB',         'validity' => '2 Days',  'autopilot_id' => 'MTN_DG_1.5GB_2DAYS',              'amount' => 650,  'amount_agent' => 650,  'sort_order' => 7],
            ['network_key' => 'mtn', 'data_type' => 'awoof', 'plan_name' => '2GB',           'validity' => '2 Days',  'autopilot_id' => 'MTN_DG_2GB_2DAYS',                'amount' => 820,  'amount_agent' => 820,  'sort_order' => 8],
            ['network_key' => 'mtn', 'data_type' => 'awoof', 'plan_name' => '3.5GB',         'validity' => '7 Days',  'autopilot_id' => 'MTN_DG_3.5GB_7DAYS',              'amount' => 1600, 'amount_agent' => 1600, 'sort_order' => 9],
            ['network_key' => 'mtn', 'data_type' => 'awoof', 'plan_name' => '6GB',           'validity' => '7 Days',  'autopilot_id' => 'MTN_DG_6GB_7DAYS',                'amount' => 2700, 'amount_agent' => 2670, 'sort_order' => 10],

            // ══════════════ MTN GIFTING ══════════════
            ['network_key' => 'mtn', 'data_type' => 'gifting', 'plan_name' => '1.8GB + 30 mins call', 'validity' => '30 Days', 'autopilot_id' => 'MTN_DG_THRYVE_DATA_1', 'amount' => 1800,  'amount_agent' => 1800,  'sort_order' => 1],
            ['network_key' => 'mtn', 'data_type' => 'gifting', 'plan_name' => '10GB + ₦3K Airtime',    'validity' => '30 Days', 'autopilot_id' => 'MTN_DG_THRYVE_DATA_2', 'amount' => 3700,  'amount_agent' => 3700,  'sort_order' => 2],
            ['network_key' => 'mtn', 'data_type' => 'gifting', 'plan_name' => '16.5GB',                'validity' => '30 Days', 'autopilot_id' => 'MTN_DG_16.5GB_30DAYS', 'amount' => 6950,  'amount_agent' => 6800,  'sort_order' => 3],
            ['network_key' => 'mtn', 'data_type' => 'gifting', 'plan_name' => '36GB',                  'validity' => '30 Days', 'autopilot_id' => 'MTN_DG_36GB_30DAYS',   'amount' => 10980, 'amount_agent' => 10980, 'sort_order' => 4],

            // ══════════════ AIRTEL CORPORATE GIFTING (CG) ══════════════
            ['network_key' => 'airtel', 'data_type' => 'cg', 'plan_name' => '100MB Daily',  'validity' => '1 Day',   'autopilot_id' => 'AIRTEL_DG_100MB_DAILY',    'amount' => 130,  'amount_agent' => 130,  'sort_order' => 1],
            ['network_key' => 'airtel', 'data_type' => 'cg', 'plan_name' => '200MB',        'validity' => '2 Days',  'autopilot_id' => 'AIRTEL_DG_200MB_2DAYS',    'amount' => 230,  'amount_agent' => 230,  'sort_order' => 2],
            ['network_key' => 'airtel', 'data_type' => 'cg', 'plan_name' => '300MB',        'validity' => '2 Days',  'autopilot_id' => 'AIRTEL_DG_300MB_2DAYS',    'amount' => 330,  'amount_agent' => 330,  'sort_order' => 3],
            ['network_key' => 'airtel', 'data_type' => 'cg', 'plan_name' => '500MB',        'validity' => '7 Days',  'autopilot_id' => 'AIRTEL_DG_500MB_7DAYS',    'amount' => 515,  'amount_agent' => 515,  'sort_order' => 4],
            ['network_key' => 'airtel', 'data_type' => 'cg', 'plan_name' => '1GB',          'validity' => '7 Days',  'autopilot_id' => 'AIRTEL_DG_1GB_7DAYS',      'amount' => 810,  'amount_agent' => 810,  'sort_order' => 5],
            ['network_key' => 'airtel', 'data_type' => 'cg', 'plan_name' => '1.5GB',        'validity' => '30 Days', 'autopilot_id' => 'AIRTEL_DG_1.5GB_7DAYS',    'amount' => 1010, 'amount_agent' => 1010, 'sort_order' => 6],
            ['network_key' => 'airtel', 'data_type' => 'cg', 'plan_name' => '2GB',          'validity' => '30 Days', 'autopilot_id' => 'AIRTEL_DG_2GB_30DAYS',     'amount' => 1495, 'amount_agent' => 1495, 'sort_order' => 7],
            ['network_key' => 'airtel', 'data_type' => 'cg', 'plan_name' => '3GB',          'validity' => '30 Days', 'autopilot_id' => 'AIRTEL_DG_3GB_30DAYS',     'amount' => 1990, 'amount_agent' => 1990, 'sort_order' => 8],
            ['network_key' => 'airtel', 'data_type' => 'cg', 'plan_name' => '4GB',          'validity' => '30 Days', 'autopilot_id' => 'AIRTEL_DG_4GB_30DAYS',     'amount' => 2485, 'amount_agent' => 2485, 'sort_order' => 9],
            ['network_key' => 'airtel', 'data_type' => 'cg', 'plan_name' => '8GB',          'validity' => '30 Days', 'autopilot_id' => 'AIRTEL_DG_8GB_30DAYS',     'amount' => 2970, 'amount_agent' => 2970, 'sort_order' => 10],
            ['network_key' => 'airtel', 'data_type' => 'cg', 'plan_name' => '10GB',         'validity' => '30 Days', 'autopilot_id' => 'AIRTEL_DG_10GB_30DAYS',    'amount' => 3950, 'amount_agent' => 3950, 'sort_order' => 11],

            // ══════════════ AIRTEL AWOOF ══════════════
            ['network_key' => 'airtel', 'data_type' => 'awoof', 'plan_name' => '150MB Daily','validity' => '1 Day',   'autopilot_id' => 'AIRTEL_AWOOF_1',   'amount' => 130,  'amount_agent' => 130,  'sort_order' => 1],
            ['network_key' => 'airtel', 'data_type' => 'awoof', 'plan_name' => '300MB',      'validity' => '2 Days',  'autopilot_id' => 'AIRTEL_AWOOF_2',   'amount' => 170,  'amount_agent' => 170,  'sort_order' => 2],
            ['network_key' => 'airtel', 'data_type' => 'awoof', 'plan_name' => '600MB',      'validity' => '2 Days',  'autopilot_id' => 'AIRTEL_AWOOF_2_1', 'amount' => 300,  'amount_agent' => 300,  'sort_order' => 3],
            ['network_key' => 'airtel', 'data_type' => 'awoof', 'plan_name' => '1GB',        'validity' => '1 Day',   'autopilot_id' => 'AIRTEL_AWOOF_2_2', 'amount' => 395,  'amount_agent' => 395,  'sort_order' => 4],
            ['network_key' => 'airtel', 'data_type' => 'awoof', 'plan_name' => '2GB',        'validity' => '5 Days',  'autopilot_id' => 'AIRTEL_AWOOF_3',   'amount' => 800,  'amount_agent' => 800,  'sort_order' => 5],
            ['network_key' => 'airtel', 'data_type' => 'awoof', 'plan_name' => '3GB',        'validity' => '7 Days',  'autopilot_id' => 'AIRTEL_AWOOF_4',   'amount' => 1230, 'amount_agent' => 1230, 'sort_order' => 6],
            ['network_key' => 'airtel', 'data_type' => 'awoof', 'plan_name' => '7GB',        'validity' => '7 Days',  'autopilot_id' => 'AIRTEL_AWOOF_5',   'amount' => 2370, 'amount_agent' => 2370, 'sort_order' => 7],
            ['network_key' => 'airtel', 'data_type' => 'awoof', 'plan_name' => '10GB',       'validity' => '30 Days', 'autopilot_id' => 'AIRTEL_AWOOF_6',   'amount' => 3350, 'amount_agent' => 3350, 'sort_order' => 8],

            // ══════════════ GLO CORPORATE GIFTING (CG) ══════════════
            ['network_key' => 'glo', 'data_type' => 'cg', 'plan_name' => '200MB', 'validity' => '14 Days', 'autopilot_id' => 'GLO_CG_1', 'amount' => 120,  'amount_agent' => 110,  'sort_order' => 1],
            ['network_key' => 'glo', 'data_type' => 'cg', 'plan_name' => '500MB', 'validity' => '30 Days', 'autopilot_id' => 'GLO_CG_2', 'amount' => 175,  'amount_agent' => 170,  'sort_order' => 2],
            ['network_key' => 'glo', 'data_type' => 'cg', 'plan_name' => '1GB',   'validity' => '30 Days', 'autopilot_id' => 'GLO_CG_3', 'amount' => 460,  'amount_agent' => 460,  'sort_order' => 3],
            ['network_key' => 'glo', 'data_type' => 'cg', 'plan_name' => '2GB',   'validity' => '30 Days', 'autopilot_id' => 'GLO_CG_4', 'amount' => 920,  'amount_agent' => 920,  'sort_order' => 4],
            ['network_key' => 'glo', 'data_type' => 'cg', 'plan_name' => '3GB',   'validity' => '30 Days', 'autopilot_id' => 'GLO_CG_5', 'amount' => 1020, 'amount_agent' => 990,  'sort_order' => 5],
            ['network_key' => 'glo', 'data_type' => 'cg', 'plan_name' => '5GB',   'validity' => '30 Days', 'autopilot_id' => 'GLO_CG_6', 'amount' => 1700, 'amount_agent' => 1650, 'sort_order' => 6],
            ['network_key' => 'glo', 'data_type' => 'cg', 'plan_name' => '10GB',  'validity' => '30 Days', 'autopilot_id' => 'GLO_CG_7', 'amount' => 3400, 'amount_agent' => 3300, 'sort_order' => 7],

            // ══════════════ 9MOBILE CORPORATE GIFTING (CG) ══════════════
            ['network_key' => 'etisalat', 'data_type' => 'cg', 'plan_name' => '500MB', 'validity' => '30 Days', 'autopilot_id' => '9MOBILE_CG_4',  'amount' => 100, 'amount_agent' => 100, 'sort_order' => 1],
            ['network_key' => 'etisalat', 'data_type' => 'cg', 'plan_name' => '1GB',   'validity' => '30 Days', 'autopilot_id' => '9MOBILE_CG_5',  'amount' => 180, 'amount_agent' => 170, 'sort_order' => 2],
            ['network_key' => 'etisalat', 'data_type' => 'cg', 'plan_name' => '2GB',   'validity' => '30 Days', 'autopilot_id' => '9MOBILE_CG_7',  'amount' => 360, 'amount_agent' => 340, 'sort_order' => 3],
            ['network_key' => 'etisalat', 'data_type' => 'cg', 'plan_name' => '3GB',   'validity' => '30 Days', 'autopilot_id' => '9MOBILE_CG_8',  'amount' => 540, 'amount_agent' => 510, 'sort_order' => 4],
            ['network_key' => 'etisalat', 'data_type' => 'cg', 'plan_name' => '4GB',   'validity' => '30 Days', 'autopilot_id' => '9MOBILE_CG_9',  'amount' => 720, 'amount_agent' => 690, 'sort_order' => 5],
            ['network_key' => 'etisalat', 'data_type' => 'cg', 'plan_name' => '5GB',   'validity' => '30 Days', 'autopilot_id' => '9MOBILE_CG_11', 'amount' => 900, 'amount_agent' => 850, 'sort_order' => 6],

            // ══════════════ 9MOBILE SME (Merrybills 7uX1UQIbdk) ══════════════
            ['network_key' => 'etisalat', 'data_type' => 'sme', 'plan_name' => '300MB',  'validity' => '30 Days', 'merrybills_product_id' => '7uX1UQIbdk', 'merrybills_id' => '14', 'amount' => 135, 'amount_agent' => 135, 'sort_order' => 1],
            ['network_key' => 'etisalat', 'data_type' => 'sme', 'plan_name' => '500MB',  'validity' => '30 Days', 'merrybills_product_id' => '7uX1UQIbdk', 'merrybills_id' => '01', 'amount' => 155, 'amount_agent' => 155, 'sort_order' => 2],
            ['network_key' => 'etisalat', 'data_type' => 'sme', 'plan_name' => '1GB',    'validity' => '30 Days', 'merrybills_product_id' => '7uX1UQIbdk', 'merrybills_id' => '02', 'amount' => 190, 'amount_agent' => 180, 'sort_order' => 3],
            ['network_key' => 'etisalat', 'data_type' => 'sme', 'plan_name' => '1.5GB',  'validity' => '30 Days', 'merrybills_product_id' => '7uX1UQIbdk', 'merrybills_id' => '03', 'amount' => 285, 'amount_agent' => 270, 'sort_order' => 4],
            ['network_key' => 'etisalat', 'data_type' => 'sme', 'plan_name' => '2GB',    'validity' => '30 Days', 'merrybills_product_id' => '7uX1UQIbdk', 'merrybills_id' => '04', 'amount' => 380, 'amount_agent' => 360, 'sort_order' => 5],
            ['network_key' => 'etisalat', 'data_type' => 'sme', 'plan_name' => '3GB',    'validity' => '30 Days', 'merrybills_product_id' => '7uX1UQIbdk', 'merrybills_id' => '05', 'amount' => 570, 'amount_agent' => 540, 'sort_order' => 6],
        ];

        $rows = array_map(function (array $plan) use ($now): array {
            return array_merge([
                'vtpass_id'             => null,
                'clubkonnect_id'        => null,
                'autopilot_id'          => null,
                'merrybills_product_id' => null,
                'merrybills_id'         => null,
                'enabled'               => true,
                'created_at'            => $now,
                'updated_at'            => $now,
            ], $plan);
        }, $plans);

        DB::table('data_plans')->insert($rows);
    }

    public function down(): void
    {
        DB::table('data_plans')->truncate();

        $keys = [
            'service_data',
            'data_api_mtn','data_api_glo','data_api_airtel','data_api_etisalat',
            'data_daily_limit',
            'data_type_mtn_sme','data_type_mtn_gifting','data_type_mtn_cg','data_type_mtn_awoof',
            'data_type_glo_sme','data_type_glo_gifting','data_type_glo_cg','data_type_glo_awoof',
            'data_type_airtel_sme','data_type_airtel_gifting','data_type_airtel_cg','data_type_airtel_awoof',
            'data_type_etisalat_sme','data_type_etisalat_gifting','data_type_etisalat_cg','data_type_etisalat_awoof',
            'data_off_percentage_mtn','data_off_percentage_glo','data_off_percentage_airtel','data_off_percentage_etisalat',
            'data_agent_off_percentage_mtn','data_agent_off_percentage_glo',
            'data_agent_off_percentage_airtel','data_agent_off_percentage_etisalat',
        ];

        DB::table('app_settings')->whereIn('key', $keys)->delete();
    }
};
