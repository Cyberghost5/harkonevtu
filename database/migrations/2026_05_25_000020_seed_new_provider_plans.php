<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // ── Network ID maps ──────────────────────────────────────────────────────

    /** easyaccess uses '01','02','03','04' as network identifiers */
    private const EA_NET = ['01' => 'mtn', '02' => 'glo', '03' => 'airtel', '04' => 'etisalat'];

    /** aabaxztech and legitdataway use '1','2','3','4' */
    private const AAB_NET = ['1' => 'mtn', '2' => 'airtel', '3' => 'glo', '4' => 'etisalat'];

    /** Normalise old `type` values to our data_type keys */
    private const TYPE_MAP = [
        'sme'      => 'sme',
        'sme2'     => 'sme',
        'cg'       => 'cg',
        'awoof'    => 'awoof',
        'gifting'  => 'gifting',
        'datacard' => 'gifting',
    ];

    // ── data_plans2 rows (status=1, val_id != '00'/'000'/'011') ─────────────
    private function easyaccessPlans(): array
    {
        return [
            // MTN ('01')
            ['01','50MB',      '1 Month', 'CG',  '177',  90,    80,    1],
            ['01','150MB',     '1 Month', 'CG',  '178',  172,   170,   2],
            ['01','250MB',     '1 Month', 'CG',  '179',  255,   250,   3],
            ['01','1GB',       '1 Month', 'SME', '51',   750,   750,   5],
            ['01','2GB',       '1 Month', 'SME', '52',   1500,  1500,  6],
            ['01','3GB',       '1 Month', 'SME', '53',   2250,  2250,  7],
            ['01','10GB',      '1 Month', 'SME', '91',   7500,  7500,  9],
            ['01','500MB',     '1 Month', 'CG',  '180',  460,   460,  40],
            ['01','1GB',       '1 Month', 'CG',  '181',  850,   850,  41],
            ['01','2GB',       '1 Month', 'CG',  '182',  1700,  1700, 42],
            ['01','3GB',       '1 Month', 'CG',  '183',  2550,  2550, 43],
            ['01','5GB',       '1 Month', 'CG',  '184',  4250,  4250, 44],
            ['01','1GB',       '2 Days',  'AWOOF','230',  520,   520,  53],
            ['01','1.5GB',     '2 Days',  'AWOOF','231',  650,   650,  75],
            ['01','2GB',       '2 Days',  'AWOOF','232',  820,   820,  76],
            // GLO ('02')
            ['02','500MB',     '1 Month', 'CG',  '159',  240,   240,  34],
            ['02','1GB',       '1 Month', 'CG',  '160',  460,   460,  35],
            ['02','2GB',       '1 Month', 'CG',  '161',  920,   920,  36],
            ['02','3GB',       '1 Month', 'CG',  '162',  1380,  1380, 37],
            ['02','5GB',       '1 Month', 'CG',  '163',  2300,  2300, 38],
            ['02','10GB',      '1 Month', 'CG',  '164',  4600,  4600, 39],
            ['02','750MB',     '1 Day',   'AWOOF','210',  230,   225,  63],
            ['02','1.5GB',     '1 Day',   'AWOOF','211',  340,   335,  64],
            ['02','2.5GB',     '2 Days',  'AWOOF','212',  570,   560,  65],
            ['02','10GB',      '7 Days',  'AWOOF','213',  2300,  2250, 66],
            // Airtel ('03')
            ['03','500MB',     '1 Month', 'CG',  '1066', 300,   295,  15],
            ['03','1GB',       '1 Month', 'CG',  '107',  650,   640,  16],
            ['03','2GB',       '1 Month', 'CG',  '108',  1300,  1280, 17],
            ['03','5GB',       '1 Month', 'CG',  '109',  3250,  3200, 18],
            ['03','10GB',      '1 Month', 'CG',  '124',  6500,  6400, 19],
            ['03','15GB',      '1 Month', 'CG',  '139',  9750,  9600, 20],
            ['03','20GB',      '1 Month', 'CG',  '140',  13000, 12800,21],
            ['03','150MB',     '1 Day',   'AWOOF','202',  130,   130,  55],
            ['03','300MB',     '2 Days',  'AWOOF','203',  240,   240,  56],
            ['03','600MB',     '2 Days',  'AWOOF','204',  320,   320,  57],
            ['03','3GB',       '7 Days',  'AWOOF','207',  1270,  1270, 59],
            ['03','7GB',       '7 Days',  'AWOOF','208',  2400,  2400, 61],
            ['03','10GB',      '1 Month', 'AWOOF','209',  3300,  3270, 62],
            ['03','1GB',       '5 Days',  'AWOOF','215',  400,   400,  68],
            ['03','1.5GB',     '7 Days',  'AWOOF','67',   1030,  1000, 70],
            ['03','500MB',     '7 Days',  'Gifting','66', 550,   530,  72],
            ['03','1GB',       '7 Days',  'Gifting','238',810,   810,  73],
            // 9Mobile ('04')
            ['04','500MB',     '1 Month', 'SME', '168',  270,   260,  22],
            ['04','1GB',       '1 Month', 'SME', '128',  540,   530,  23],
            ['04','2GB',       '1 Month', 'SME', '130',  1080,  1060, 24],
            ['04','3GB',       '1 Month', 'SME', '132',  1620,  1590, 26],
            ['04','5GB',       '1 Month', 'SME', '134',  2700,  2650, 28],
            ['04','10GB',      '1 Month', 'SME', '136',  5400,  5300, 31],
            ['04','15GB',      '1 Month', 'SME', '137',  8100,  7950, 32],
            ['04','20GB',      '1 Month', 'SME', '138',  10800, 10600,33],
        ];
    }

    // ── data_plans5 rows (aabaxyztech, network: 1=mtn,2=airtel,3=glo,4=9mob) ─
    private function aabaxytechPlans(): array
    {
        return [
            // MTN ('1')
            ['1','500MB',     '7 Days',  'SME',  '1',   520,   520,   4],
            ['1','1GB',       '7 Days',  'SME',  '2',   835,   835,   5],
            ['1','2GB',       '30 Days', 'SME',  '3',   1500,  1500,  6],
            ['1','3.5GB',     '30 Days', 'SME',  '4',   2550,  2500,  7],
            ['1','1GB',       '30 Days', 'CG',   '135', 650,   650,  14],
            ['1','2GB',       '30 Days', 'CG',   '136', 1300,  1300, 15],
            ['1','500MB',     '30 Days', 'SME2', '83',  112,   110,  27],
            ['1','1GB',       '30 Days', 'SME2', '84',  215,   210,  28],
            ['1','2GB',       '30 Days', 'SME2', '85',  430,   420,  29],
            ['1','5GB',       '30 Days', 'SME2', '87',  1075,  1050, 30],
            ['1','10GB',      '30 Days', 'SME2', '90',  2150,  2100, 31],
            ['1','300MB',     '7 Days',  'SME2', '91',  90,    80,   32],
            ['1','3GB Coupon','30 Days', 'Datacard','93',750,  745,  34],
            ['1','75MB',      '1 Day',   'AWOOF','108', 130,   125, 123],
            ['1','1GB',       '1 Day',   'AWOOF','109', 510,   510, 124],
            ['1','2GB',       '2 Days',  'AWOOF','115', 799,   795, 125],
            ['1','2.5GB',     '2 Days',  'AWOOF','110', 920,   910, 126],
            ['1','3.2GB',     '2 Days',  'AWOOF','112', 1100,  1080,127],
            ['1','6GB',       '7 Days',  'AWOOF','111', 2700,  2700,128],
            ['1','7GB',       '7 Days',  'AWOOF','113', 3200,  3150,141],
            ['1','1.2GB',     '7 Days',  'AWOOF','150', 800,   800, 143],
            ['1','250MB',     '1 Day',   'AWOOF','151', 230,   230, 144],
            ['1','750MB',     '3 Days',  'AWOOF','162', 500,   495, 147],
            ['1','1.5GB',     '1 Day',   'AWOOF','118', 540,   540, 148],
            ['1','1.5GB',     '7 Days',  'AWOOF','161', 1060,  1050,149],
            ['1','10GB',      '30 Days', 'SME',  '8',   4985,  4970,150],
            // Airtel ('2')
            ['2','500MB',     '30 Days', 'CG',   '10',  410,   410,  35],
            ['2','1GB',       '30 Days', 'CG',   '11',  760,   760,  36],
            ['2','2GB',       '30 Days', 'CG',   '12',  1520,  1520, 37],
            ['2','5GB',       '30 Days', 'CG',   '13',  3800,  3800, 38],
            ['2','10GB',      '30 Days', 'CG',   '14',  7600,  7600, 39],
            ['2','150MB',     '1 Day',   'AWOOF','139', 100,   100, 129],
            ['2','300MB',     '2 Days',  'AWOOF','140', 150,   150, 130],
            ['2','1GB',       '2 Days',  'AWOOF','125', 395,   395, 131],
            ['2','2GB',       '5 Days',  'AWOOF','126', 730,   720, 132],
            ['2','3GB',       '7 Days',  'AWOOF','127', 1200,  1180,134],
            ['2','10GB',      '1 Month', 'AWOOF','129', 3350,  3300,135],
            ['2','600MB',     '2 Days',  'AWOOF','141', 260,   250, 142],
            // GLO ('3')
            ['3','200MB',     '30 Days', 'CG',   '20',  100,   95,   64],
            ['3','500MB',     '30 Days', 'CG',   '21',  240,   240,  65],
            ['3','1GB',       '30 Days', 'CG',   '22',  460,   460,  66],
            ['3','2GB',       '30 Days', 'CG',   '23',  920,   920,  67],
            ['3','3GB',       '30 Days', 'CG',   '24',  1380,  1380, 68],
            ['3','5GB',       '30 Days', 'CG',   '25',  2300,  2300, 70],
            ['3','10GB',      '30 Days', 'CG',   '26',  4600,  4600, 71],
            ['3','750MB',     '1 Day',   'AWOOF','66',  230,   225, 137],
            ['3','1.5GB',     '1 Day',   'AWOOF','67',  340,   335, 138],
            ['3','2.5GB',     '2 Days',  'AWOOF','68',  570,   560, 139],
            ['3','10GB',      '7 Days',  'AWOOF','69',  2300,  2250,140],
            // 9Mobile ('4')
            ['4','500MB',     '30 Days', 'SME',  '7',   250,   250, 101],
            ['4','1GB',       '30 Days', 'SME',  '15',  420,   420,  96],
            ['4','2GB',       '30 Days', 'SME',  '16',  850,   850,  97],
            ['4','3GB',       '30 Days', 'SME',  '17',  1275,  1275,102],
            ['4','5GB',       '30 Days', 'SME',  '18',  2130,  2130,119],
            ['4','10GB',      '30 Days', 'SME',  '19',  4250,  4250,103],
        ];
    }

    // ── data_plans6 rows (legitdataway, network: 1=mtn,2=airtel,3=glo,4=9mob)
    private function legitdatawayPlans(): array
    {
        return [
            // MTN ('1')
            ['1','500MB',     '1 Month', 'SME',    '36',  334,   332,   4],
            ['1','1GB',       '1 Month', 'SME',    '37',  669,   665,   5],
            ['1','2GB',       '1 Month', 'SME',    '38',  1338,  1380,  6],
            ['1','3GB',       '1 Month', 'SME',    '39',  2007,  1995, 135],
            ['1','10GB',      '1 Month', 'SME',    '41',  6990,  6900, 137],
            ['1','500MB',     '1 Month', 'CG',     '42',  145,   140,  13],
            ['1','1GB',       '1 Month', 'CG',     '46',  280,   275,  14],
            ['1','2GB',       '1 Month', 'CG',     '47',  560,   550,  15],
            ['1','3GB',       '1 Month', 'CG',     '48',  840,   825,  16],
            ['1','5GB',       '1 Month', 'CG',     '49',  1400,  1375, 17],
            ['1','10GB',      '1 Month', 'CG',     '50',  2800,  2750, 18],
            ['1','500MB',     '30 Days', 'Gifting','970',  128,   125, 130],
            ['1','1GB',       '30 Days', 'Gifting','980',  255,   245, 131],
            ['1','2GB',       '30 Days', 'Gifting','940',  510,   490, 132],
            ['1','3GB',       '30 Days', 'Gifting','950',  765,   735, 133],
            ['1','5GB',       '30 Days', 'Gifting','960',  1275,  1225,134],
            // Airtel ('2') → network_key='airtel'
            ['2','500MB',     '30 Days', 'CG',    '51',   322,   320,  35],
            ['2','1GB',       '30 Days', 'CG',    '52',   650,   645,  36],
            ['2','2GB',       '30 Days', 'CG',    '53',   1300,  1280, 37],
            ['2','5GB',       '30 Days', 'CG',    '54',   3250,  3200, 38],
            ['2','10GB',      '30 Days', 'CG',    '55',   6500,  6400, 39],
            // GLO ('3') → network_key='glo'
            ['3','200MB',     '30 Days', 'CG',    '70',   100,   95,   64],
            ['3','500MB',     '30 Days', 'CG',    '71',   140,   140,  65],
            ['3','1GB',       '30 Days', 'CG',    '72',   290,   285,  66],
            ['3','2GB',       '30 Days', 'CG',    '73',   580,   570,  67],
            ['3','3GB',       '30 Days', 'CG',    '74',   870,   855,  68],
            ['3','5GB',       '30 Days', 'CG',    '75',   1450,  1425, 70],
            ['3','10GB',      '30 Days', 'CG',    '76',   2900,  2850, 71],
            ['3','1GB',       '1 Day',   'AWOOF', '107',  230,   225, 138],
            ['3','2GB',       '1 Day',   'AWOOF', '108',  340,   335, 139],
            ['3','3.5GB',     '2 Days',  'AWOOF', '109',  570,   560, 140],
            ['3','15GB',      '7 Days',  'AWOOF', '110',  2300,  2250,141],
            // 9Mobile ('4') → network_key='etisalat'
            ['4','500MB',     '1 Month', 'SME',   '85',   130,   120, 101],
            ['4','1GB',       '1 Month', 'SME',   '86',   190,   180,  96],
            ['4','1.5GB',     '1 Month', 'SME',   '17',   300,   255,  98],
            ['4','2GB',       '1 Month', 'SME',   '87',   380,   360,  97],
            ['4','3GB',       '1 Month', 'SME',   '88',   570,   540, 102],
            ['4','4GB',       '1 Month', 'SME',   '89',   665,   650, 116],
            ['4','5GB',       '1 Month', 'SME',   '90',   1000,  850, 119],
            ['4','10GB',      '1 Month', 'SME',   '99',   1900,  1800,103],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function up(): void
    {
        // ── 1. Correct network_airtime provider IDs (seeded wrong in migration 19) ─
        $corrections = [
            'mtn'      => ['easyaccess_id' => '01', 'aabaxztech_id' => '1', 'legitdataway_id' => '1'],
            'glo'      => ['easyaccess_id' => '02', 'aabaxztech_id' => '3', 'legitdataway_id' => '3'],
            'airtel'   => ['easyaccess_id' => '03', 'aabaxztech_id' => '2', 'legitdataway_id' => '2'],
            'etisalat' => ['easyaccess_id' => '04', 'aabaxztech_id' => '4', 'legitdataway_id' => '4'],
        ];
        foreach ($corrections as $networkKey => $ids) {
            DB::table('network_airtime')->where('network_key', $networkKey)->update($ids);
        }

        $now = now();

        // ── 2. Seed easyaccess plans ─────────────────────────────────────────
        foreach ($this->easyaccessPlans() as $row) {
            [$netId, $plan, $validity, $type, $valId, $amount, $amountAgent, $sortOrder] = $row;
            $networkKey = self::EA_NET[$netId] ?? null;
            $dataType   = self::TYPE_MAP[strtolower($type)] ?? null;
            if (!$networkKey || !$dataType) {
                continue;
            }
            DB::table('data_plans')->insert([
                'network_key'    => $networkKey,
                'data_type'      => $dataType,
                'plan_name'      => trim($plan),
                'validity'       => trim($validity),
                'easyaccess_id'  => $valId,
                'amount'         => $amount,
                'amount_agent'   => $amountAgent,
                'enabled'        => true,
                'sort_order'     => $sortOrder,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }

        // ── 3. Seed aabaxyztech plans ─────────────────────────────────────────
        foreach ($this->aabaxytechPlans() as $row) {
            [$netId, $plan, $validity, $type, $valId, $amount, $amountAgent, $sortOrder] = $row;
            $networkKey = self::AAB_NET[$netId] ?? null;
            $dataType   = self::TYPE_MAP[strtolower($type)] ?? null;
            if (!$networkKey || !$dataType) {
                continue;
            }
            DB::table('data_plans')->insert([
                'network_key'   => $networkKey,
                'data_type'     => $dataType,
                'plan_name'     => trim($plan),
                'validity'      => trim($validity),
                'aabaxztech_id' => $valId,
                'amount'        => $amount,
                'amount_agent'  => $amountAgent,
                'enabled'       => true,
                'sort_order'    => $sortOrder,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        // ── 4. Seed legitdataway plans ────────────────────────────────────────
        foreach ($this->legitdatawayPlans() as $row) {
            [$netId, $plan, $validity, $type, $valId, $amount, $amountAgent, $sortOrder] = $row;
            $networkKey = self::AAB_NET[$netId] ?? null;   // same mapping as aabaxztech
            $dataType   = self::TYPE_MAP[strtolower($type)] ?? null;
            if (!$networkKey || !$dataType) {
                continue;
            }
            DB::table('data_plans')->insert([
                'network_key'     => $networkKey,
                'data_type'       => $dataType,
                'plan_name'       => trim($plan),
                'validity'        => trim($validity),
                'legitdataway_id' => $valId,
                'amount'          => $amount,
                'amount_agent'    => $amountAgent,
                'enabled'         => true,
                'sort_order'      => $sortOrder,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Remove all plans that only have a new-provider ID (no legacy IDs)
        DB::table('data_plans')
            ->whereNull('vtpass_id')
            ->whereNull('clubkonnect_id')
            ->whereNull('autopilot_id')
            ->whereNull('merrybills_id')
            ->where(function ($q) {
                $q->whereNotNull('easyaccess_id')
                  ->orWhereNotNull('aabaxztech_id')
                  ->orWhereNotNull('legitdataway_id');
            })
            ->delete();

        // Revert network_airtime to the (wrong) values from migration 19
        $revert = [
            'mtn'      => ['easyaccess_id' => '1', 'aabaxztech_id' => '1', 'legitdataway_id' => '1'],
            'glo'      => ['easyaccess_id' => '2', 'aabaxztech_id' => '2', 'legitdataway_id' => '2'],
            'airtel'   => ['easyaccess_id' => '4', 'aabaxztech_id' => '4', 'legitdataway_id' => '4'],
            'etisalat' => ['easyaccess_id' => '3', 'aabaxztech_id' => '3', 'legitdataway_id' => '3'],
        ];
        foreach ($revert as $networkKey => $ids) {
            DB::table('network_airtime')->where('network_key', $networkKey)->update($ids);
        }
    }
};
