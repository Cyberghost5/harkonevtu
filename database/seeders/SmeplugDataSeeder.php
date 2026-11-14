<?php

namespace Database\Seeders;

use App\Models\DataPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SmeplugDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = base_path('docs/smeplug-data/all.json');

        if (!File::exists($filePath)) {
            $this->command?->warn("SME Plug data file not found: {$filePath}");
            return;
        }

        $json = json_decode(File::get($filePath), true);
        $data = $json['data'] ?? [];

        if (empty($data)) {
            $this->command?->warn("No data found in SME Plug data file: {$filePath}");
            return;
        }

        $networkMap = [
            '1' => ['network_key' => 'mtn',      'name' => 'MTN'],
            '2' => ['network_key' => 'airtel',   'name' => 'Airtel'],
            '3' => ['network_key' => 'etisalat', 'name' => '9Mobile'],
            '4' => ['network_key' => 'glo',      'name' => 'Glo'],
        ];

        $totalProcessed = 0;
        $totalCreated   = 0;
        $totalUpdated   = 0;

        foreach ($networkMap as $netId => $netInfo) {
            $networkKey  = $netInfo['network_key'];
            $networkName = $netInfo['name'];
            $plansList   = $data[$netId] ?? [];

            $this->command?->info("Processing {$networkName} (Network {$netId}) - " . count($plansList) . " plans...");

            $maxSortOrder = (int) DataPlan::where('network_key', $networkKey)->max('sort_order');
            $sortOrder    = $maxSortOrder + 1;

            foreach ($plansList as $item) {
                $totalProcessed++;

                $planId     = trim((string) ($item['id'] ?? ''));
                $rawName    = trim((string) ($item['name'] ?? ''));
                $price      = (float) ($item['price'] ?? 0);
                $telcoPrice = (float) ($item['telco_price'] ?? 0);
                $baseCost   = $price > 0 ? $price : $telcoPrice;

                if ($planId === '') {
                    continue;
                }

                $dataType = $this->resolveDataType($rawName);
                $validity = $this->extractValidity($rawName);
                $planName = $this->formatPlanName($rawName);

                $amountAgent = $baseCost > 0 ? $baseCost : 0;
                $amountUser  = $baseCost > 0 ? ($baseCost + 20.00) : 0;

                // 1. Try to find existing plan already linked to this smeplug_id
                $plan = DataPlan::where('smeplug_id', $planId)->first();

                // 2. Try to match existing plan by (network_key, data_type, plan_name, validity) where smeplug_id is null
                if (!$plan) {
                    $plan = DataPlan::where('network_key', $networkKey)
                        ->where('data_type', $dataType)
                        ->where('plan_name', $planName)
                        ->where('validity', $validity)
                        ->whereNull('smeplug_id')
                        ->first();
                }

                // 3. Fallback match by (network_key, data_type, plan_name) where smeplug_id is null
                if (!$plan) {
                    $plan = DataPlan::where('network_key', $networkKey)
                        ->where('data_type', $dataType)
                        ->where('plan_name', $planName)
                        ->whereNull('smeplug_id')
                        ->first();
                }

                // 4. Fallback match by (network_key, plan_name, validity) where smeplug_id is null
                if (!$plan) {
                    $plan = DataPlan::where('network_key', $networkKey)
                        ->where('plan_name', $planName)
                        ->where('validity', $validity)
                        ->whereNull('smeplug_id')
                        ->first();
                }

                if ($plan) {
                    $updateData = [
                        'smeplug_id' => $planId,
                        'enabled'    => true,
                    ];
                    // If existing plan has zero amount, fill in resolved prices
                    if (empty($plan->amount) || (float) $plan->amount <= 0) {
                        $updateData['amount'] = $amountUser;
                    }
                    if (empty($plan->amount_agent) || (float) $plan->amount_agent <= 0) {
                        $updateData['amount_agent'] = $amountAgent;
                    }
                    $plan->update($updateData);
                    $totalUpdated++;
                } else {
                    // Only create a brand new plan if it has a valid positive base cost
                    if ($baseCost <= 0) {
                        continue;
                    }

                    DataPlan::create([
                        'network_key'  => $networkKey,
                        'data_type'    => $dataType,
                        'plan_name'    => $planName,
                        'validity'     => $validity,
                        'smeplug_id'   => $planId,
                        'amount'       => $amountUser,
                        'amount_agent' => $amountAgent,
                        'enabled'      => true,
                        'sort_order'   => $sortOrder++,
                    ]);
                    $totalCreated++;
                }
            }
        }

        $this->command?->info("SmeplugDataSeeder completed: {$totalProcessed} processed ({$totalCreated} created, {$totalUpdated} updated).");
    }

    /**
     * Resolve VTU standard data_type from SME Plug plan name and tags.
     */
    private function resolveDataType(string $name): string
    {
        $nm = strtolower($name);

        if (str_contains($nm, 'awoof')) {
            return 'awoof';
        }
        if (str_contains($nm, 'corporate') || str_contains($nm, 'cg')) {
            return 'cg';
        }
        if (str_contains($nm, 'share') || str_contains($nm, 'sme')) {
            return 'sme';
        }
        if (str_contains($nm, 'gift')) {
            return 'gifting';
        }

        return 'cg';
    }

    /**
     * Extract standardized validity from SME Plug plan name.
     */
    private function extractValidity(string $name): string
    {
        if (preg_match('/\b(365\s*days?|1\s*year|yearly)\b/i', $name)) return '365 Days';
        if (preg_match('/\b(180\s*days?|6\s*months?)\b/i', $name))     return '180 Days';
        if (preg_match('/\b(120\s*days?)\b/i', $name))                  return '120 Days';
        if (preg_match('/\b(90\s*days?|3\s*months?)\b/i', $name))       return '90 Days';
        if (preg_match('/\b(60\s*days?|2\s*months?)\b/i', $name))       return '60 Days';
        if (preg_match('/\b(30\s*days?|monthly|1\s*month)\b/i', $name)) return '30 Days';
        if (preg_match('/\b(15\s*days?)\b/i', $name))                  return '15 Days';
        if (preg_match('/\b(14\s*days?|2\s*weeks?)\b/i', $name))       return '14 Days';
        if (preg_match('/\b(10\s*days?)\b/i', $name))                  return '10 Days';
        if (preg_match('/\b(7\s*days?|weekly|1\s*week)\b/i', $name))   return '7 Days';
        if (preg_match('/\b(5\s*days?)\b/i', $name))                  return '5 Days';
        if (preg_match('/\b(3\s*days?)\b/i', $name))                  return '3 Days';
        if (preg_match('/\b(2\s*days?)\b/i', $name))                  return '2 Days';
        if (preg_match('/\b(1\s*days?|daily|24\s*hrs?|24\s*hours?)\b/i', $name)) return '1 Day';

        return '30 Days';
    }

    /**
     * Format clean plan name extracting just the data bundle size (e.g. 500MB, 1.5GB, 10GB).
     */
    private function formatPlanName(string $name): string
    {
        // 1. Match size like 110MB, 1.5 GB, 500MB, 10GB, 2.6 GB
        if (preg_match('/(\d+(?:\.\d+)?)\s*(MB|GB|TB)\b/i', $name, $matches)) {
            return strtoupper(trim($matches[1]) . trim($matches[2]));
        }

        // 2. Clean fallback
        $clean = trim($name);
        $clean = preg_replace('/\s+/', ' ', $clean);
        return mb_substr($clean, 0, 100);
    }
}
