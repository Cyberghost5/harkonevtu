<?php

namespace Database\Seeders;

use App\Models\DataPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class VTPassDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $networks = [
            // 1. MTN Data (Starting with MTN as requested)
            [
                'network_key'   => 'mtn',
                'file_path'     => base_path('docs/vtpass-data/mtn.json'),
                'type_resolver' => function (string $name): string {
                    // Daily/weekly plans categorized as SME; monthly, yearly, broadband as Gifting
                    if (preg_match('/\b(30\s*days?|monthly|month|60\s*days?|90\s*days?|365\s*days?|yearly|router|broadband)\b/i', $name)) {
                        return 'gifting';
                    }
                    return 'sme';
                },
            ],

            // 2. GLO SME Data (Best Value)
            [
                'network_key' => 'glo',
                'file_path'   => base_path('docs/vtpass-data/glo-sme.json'),
                'data_type'   => 'sme',
            ],

            // 3. GLO CG Data (Standard Plans)
            [
                'network_key' => 'glo',
                'file_path'   => base_path('docs/vtpass-data/glo.json'),
                'data_type'   => 'cg',
            ],

            // 4. Airtel Data (CG)
            [
                'network_key' => 'airtel',
                'file_path'   => base_path('docs/vtpass-data/airtel.json'),
                'data_type'   => 'cg',
            ],

            // 5. 9mobile / T2 Data (CG)
            [
                'network_key' => 'etisalat',
                'file_path'   => base_path('docs/vtpass-data/9mobile.json'),
                'data_type'   => 'cg',
            ],
        ];

        $totalProcessed = 0;
        $totalCreated   = 0;
        $totalUpdated   = 0;

        foreach ($networks as $config) {
            $networkKey = $config['network_key'];
            $filePath   = $config['file_path'];

            if (!File::exists($filePath)) {
                $this->command?->warn("VTpass data file not found: {$filePath}");
                continue;
            }

            $json = json_decode(File::get($filePath), true);
            $variations = $json['content']['variations'] ?? [];

            $this->command?->info("Processing {$networkKey} from " . basename($filePath) . " (" . count($variations) . " variations)...");

            $sortOrder = 1;

            foreach ($variations as $variation) {
                $totalProcessed++;

                $vtpassId   = trim((string) ($variation['variation_code'] ?? ''));
                $rawName    = trim((string) ($variation['name'] ?? ''));
                $baseAmount = (float) ($variation['variation_amount'] ?? 0);

                if ($vtpassId === '' || $baseAmount <= 0) {
                    continue;
                }

                // Determine data_type
                if (isset($config['type_resolver']) && is_callable($config['type_resolver'])) {
                    $dataType = ($config['type_resolver'])($rawName);
                } else {
                    $dataType = $config['data_type'] ?? 'sme';
                }

                // Normal user amount is wholesale + 20, agent amount is wholesale base price
                $amountAgent = $baseAmount;
                $amountUser  = $baseAmount + 20.00;

                // Extract validity and plan name
                $validity = $this->extractValidity($rawName);
                $planName = $this->formatPlanName($rawName);

                // Check if existing plan matches (network_key, data_type, vtpass_id)
                $plan = DataPlan::where('network_key', $networkKey)
                    ->where('data_type', $dataType)
                    ->where('vtpass_id', $vtpassId)
                    ->first();

                if ($plan) {
                    $plan->update([
                        'plan_name'    => $planName,
                        'validity'     => $validity,
                        'amount'       => $amountUser,
                        'amount_agent' => $amountAgent,
                        'enabled'      => true,
                        'sort_order'   => $sortOrder++,
                    ]);
                    $totalUpdated++;
                } else {
                    DataPlan::create([
                        'network_key'  => $networkKey,
                        'data_type'    => $dataType,
                        'plan_name'    => $planName,
                        'validity'     => $validity,
                        'vtpass_id'    => $vtpassId,
                        'amount'       => $amountUser,
                        'amount_agent' => $amountAgent,
                        'enabled'      => true,
                        'sort_order'   => $sortOrder++,
                    ]);
                    $totalCreated++;
                }
            }
        }

        $this->command?->info("VTPassDataSeeder completed: {$totalProcessed} processed ({$totalCreated} created, {$totalUpdated} updated).");
    }

    /**
     * Extract standardized validity from variation name.
     */
    private function extractValidity(string $name): string
    {
        if (preg_match('/\b(365\s*days?|1\s*year|yearly)\b/i', $name)) return '365 Days';
        if (preg_match('/\b(120\s*days?)\b/i', $name)) return '120 Days';
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
        if (preg_match('/\b(1\s*day|daily|24\s*hrs?|24\s*hours?)\b/i', $name)) return '1 Day';

        return '30 Days';
    }

    /**
     * Clean and format plan name cleanly within column constraints.
     */
    private function formatPlanName(string $name): string
    {
        $clean = trim($name);
        // Normalize any weird double spaces
        $clean = preg_replace('/\s+/', ' ', $clean);

        return mb_substr($clean, 0, 120);
    }
}
