<?php

namespace Database\Seeders;

use App\Models\ExamPinType;
use Illuminate\Database\Seeder;

class ExamPinTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name'                    => 'WAEC Result Checker PIN',
                'slug'                    => 'waec',
                'amount'                  => 3600.00,
                'vtpass_service_id'       => 'waec',
                'easyaccess_endpoint'     => '/api/waec_v2.php',
                'primebiller_provider_id' => 1,
                'instructions'           => 'Visit waecdirect.org, click "Check Result", enter your scratch card PIN and serial number.',
                'is_active'               => true,
            ],
            [
                'name'                    => 'NECO Result Checker PIN',
                'slug'                    => 'neco',
                'amount'                  => 1400.00,
                'vtpass_service_id'       => 'neco',
                'easyaccess_endpoint'     => '/api/neco_v2.php',
                'primebiller_provider_id' => 2,
                'instructions'           => 'Visit results.neco.gov.ng, select your exam, enter PIN and serial number.',
                'is_active'               => true,
            ],
            [
                'name'                    => 'NABTEB Result Checker PIN',
                'slug'                    => 'nabteb',
                'amount'                  => 1000.00,
                'vtpass_service_id'       => 'nabteb',
                'easyaccess_endpoint'     => '/api/nabteb_v2.php',
                'primebiller_provider_id' => 3,
                'instructions'           => 'Visit eworld.nabtebnigeria.org, log in, enter your PIN and serial number to view results.',
                'is_active'               => true,
            ],
            [
                'name'                    => 'NBAIS Result Checker PIN',
                'slug'                    => 'nbais',
                'amount'                  => 1000.00,
                'vtpass_service_id'       => null,
                'easyaccess_endpoint'     => '/api/nbais_v2.php',
                'primebiller_provider_id' => 4,
                'instructions'           => 'Visit NBAIS portal, enter your PIN and serial number to check your result.',
                'is_active'               => false,
            ],
        ];

        foreach ($types as $type) {
            ExamPinType::updateOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
