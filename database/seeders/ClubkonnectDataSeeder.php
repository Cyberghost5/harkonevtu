<?php

namespace Database\Seeders;

use App\Models\DataPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ClubkonnectDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $networks = [
            [
                'network_key' => 'mtn',
                'name'        => 'MTN',
                'file_path'   => base_path('docs/clubkonnect-data/mtn.json'),
            ],
            [
                'network_key' => 'glo',
                'name'        => 'Glo',
                'file_path'   => base_path('docs/clubkonnect-data/glo.json'),
            ],
            [
                'network_key' => 'airtel',
                'name'        => 'Airtel',
                'file_path'   => base_path('docs/clubkonnect-data/airtel.json'),
            ],
            [
                'network_key' => 'etisalat',
                'name'        => '9Mobile',
                'file_path'   => base_path('docs/clubkonnect-data/9mobile.json'),
            ],
        ];

        $totalProcessed = 0;
        $totalCreated   = 0;
        $totalUpdated   = 0;

        foreach ($networks as $config) {
            $networkKey  = $config['network_key'];
            $networkName = $config['name'];
            $filePath    = $config['file_path'];

            if (!File::exists($filePath)) {
                $this->command?->warn("Clubkonnect data file not found: {$filePath}");
                continue;
            }

            $json          = json_decode(File::get($filePath), true);
            $mobileNetwork = $json['MOBILE_NETWORK'] ?? [];
            $rootKey       = array_key_first($mobileNetwork);
            $products      = $mobileNetwork[$rootKey][0]['PRODUCT'] ?? [];

            $this->command?->info("Processing {$networkName} from " . basename($filePath) . " (" . count($products) . " products)...");

            $maxSortOrder = (int) DataPlan::where('network_key', $networkKey)->max('sort_order');
            $sortOrder    = $maxSortOrder + 1;

            foreach ($products as $item) {
                $totalProcessed++;

                $planCode   = trim((string) ($item['PRODUCT_CODE'] ?? ''));
                $rawName    = trim((string) ($item['PRODUCT_NAME'] ?? ''));
                $rawType    = trim((string) ($item['DATA_TYPE'] ?? ''));
                $baseAmount = (float) ($item['PRODUCT_AMOUNT'] ?? 0);

                if ($planCode === '' || $baseAmount <= 0) {
                    continue;
                }

                $dataType = $this->resolveDataType($rawType, $rawName);
                $validity = $this->extractValidity($rawName);
                $planName = $this->formatPlanName($rawName);

                $amountAgent = $baseAmount;
                $amountUser  = $baseAmount + 20.00;

                // 1. Try to find existing plan with this clubkonnect_id for this network
                $plan = DataPlan::where('network_key', $networkKey)
                    ->where('clubkonnect_id', $planCode)
                    ->first();

                // 2. If not found, try to match existing plan by (network_key, data_type, plan_name, validity) where clubkonnect_id is null
                if (!$plan) {
                    $plan = DataPlan::where('network_key', $networkKey)
                        ->where('data_type', $dataType)
                        ->where('plan_name', $planName)
                        ->where('validity', $validity)
                        ->whereNull('clubkonnect_id')
                        ->first();
                }

                // 3. Fallback match by (network_key, data_type, plan_name) where clubkonnect_id is null
                if (!$plan) {
                    $plan = DataPlan::where('network_key', $networkKey)
                        ->where('data_type', $dataType)
                        ->where('plan_name', $planName)
                        ->whereNull('clubkonnect_id')
                        ->first();
                }

                if ($plan) {
                    $plan->update([
                        'clubkonnect_id' => $planCode,
                        'plan_name'      => $plan->plan_name ?: $planName,
                        'validity'       => $plan->validity ?: $validity,
                        'amount'         => $plan->amount ?: $amountUser,
                        'amount_agent'   => $plan->amount_agent ?: $amountAgent,
                        'enabled'        => true,
                    ]);
                    $totalUpdated++;
                } else {
                    DataPlan::create([
                        'network_key'    => $networkKey,
                        'data_type'      => $dataType,
                        'plan_name'      => $planName,
                        'validity'       => $validity,
                        'clubkonnect_id' => $planCode,
                        'amount'         => $amountUser,
                        'amount_agent'   => $amountAgent,
                        'enabled'        => true,
                        'sort_order'     => $sortOrder++,
                    ]);
                    $totalCreated++;
                }
            }
        }

        $this->command?->info("ClubkonnectDataSeeder completed: {$totalProcessed} processed ({$totalCreated} created, {$totalUpdated} updated).");
    }

    /**
     * Resolve VTU standard data_type from Clubkonnect DATA_TYPE and product name.
     */
    private function resolveDataType(string $rawType, string $name): string
    {
        $rawType   = strtoupper(trim($rawType));
        $nameLower = strtolower($name);

        if (str_contains($nameLower, 'awoof') || str_contains($nameLower, 'awooof')) {
            return 'awoof';
        }

        if ($rawType === 'SME') {
            return 'sme';
        }

        if (str_contains($nameLower, 'corporate') || str_contains($nameLower, 'cg')) {
            return 'cg';
        }

        // Direct Data bundles default to gifting
        return 'gifting';
    }

    /**
     * Extract standardized validity from product name.
     */
    private function extractValidity(string $name): string
    {
        if (preg_match('/\b(365\s*days?|1\s*year|yearly)\b/i', $name)) return '365 Days';
        if (preg_match('/\b(180\s*days?|6\s*months?)\b/i', $name)) return '180 Days';
        if (preg_match('/\b(120\s*days?|4\s*months?)\b/i', $name)) return '120 Days';
        if (preg_match('/\b(90\s*days?|3\s*months?)\b/i', $name)) return '90 Days';
        if (preg_match('/\b(60\s*days?|2\s*months?)\b/i', $name)) return '60 Days';
        if (preg_match('/\b(30\s*days?|monthly|1\s*month)\b/i', $name)) return '30 Days';
        if (preg_match('/\b(15\s*days?)\b/i', $name)) return '15 Days';
        if (preg_match('/\b(14\s*days?|2\s*weeks?)\b/i', $name)) return '14 Days';
        if (preg_match('/\b(10\s*days?)\b/i', $name)) return '10 Days';
        if (preg_match('/\b(7\s*days?|weekly|1\s*week)\b/i', $name)) return '7 Days';
        if (preg_match('/\b(5\s*days?)\b/i', $name)) return '5 Days';
        if (preg_match('/\b(3\s*days?)\b/i', $name)) return '3 Days';
        if (preg_match('/\b(2\s*days?)\b/i', $name)) return '2 Days';
        if (preg_match('/\b(1\s*days?|daily|24\s*hrs?|24\s*hours?)\b/i', $name)) return '1 Day';

        return '30 Days';
    }

    /**
     * Format clean plan name (e.g. 500MB, 1GB, 1.5GB, 1TB).
     */
    private function formatPlanName(string $name): string
    {
        if (preg_match('/(\d+(?:\.\d+)?)\s*(MB|GB|TB)\b/i', $name, $matches)) {
            return strtoupper($matches[1] . $matches[2]);
        }

        $clean = trim($name);
        $clean = preg_replace('/\s+/', ' ', $clean);
        return mb_substr($clean, 0, 100);
    }
}
