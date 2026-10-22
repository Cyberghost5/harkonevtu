<?php

namespace Database\Seeders;

use App\Models\DataPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class PayscribeDataSeeder extends Seeder
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
                'file_path'   => base_path('docs/payscribe-data/mtn.json'),
            ],
            [
                'network_key' => 'glo',
                'name'        => 'Glo',
                'file_path'   => base_path('docs/payscribe-data/glo.json'),
            ],
            [
                'network_key' => 'airtel',
                'name'        => 'Airtel',
                'file_path'   => base_path('docs/payscribe-data/airtel.json'),
            ],
            [
                'network_key' => 'etisalat',
                'name'        => '9Mobile',
                'file_path'   => base_path('docs/payscribe-data/9mobile.json'),
            ],
        ];

        $totalProcessed = 0;
        $totalCreated   = 0;
        $totalUpdated   = 0;

        foreach ($networks as $config) {
            $networkKey = $config['network_key'];
            $networkName = $config['name'];
            $filePath   = $config['file_path'];

            if (!File::exists($filePath)) {
                $this->command?->warn("Payscribe data file not found: {$filePath}");
                continue;
            }

            $json    = json_decode(File::get($filePath), true);
            $details = $json['message']['details'] ?? [];

            $this->command?->info("Processing {$networkName} from " . basename($filePath) . "...");

            $maxSortOrder = (int) DataPlan::where('network_key', $networkKey)->max('sort_order');
            $sortOrder = $maxSortOrder + 1;

            foreach ($details as $detail) {
                $categories = $detail['plans'] ?? [];

                foreach ($categories as $categoryGroup) {
                    $categoryName = trim((string) ($categoryGroup['category'] ?? ''));
                    $plansList    = $categoryGroup['plans'] ?? [];

                    foreach ($plansList as $planItem) {
                        $totalProcessed++;

                        $planCode   = trim((string) ($planItem['plan_code'] ?? ''));
                        $rawName    = trim((string) ($planItem['name'] ?? ''));
                        $alias      = trim((string) ($planItem['alias'] ?? ''));
                        $baseAmount = (float) ($planItem['amount'] ?? 0);

                        if ($planCode === '' || $baseAmount <= 0) {
                            continue;
                        }

                        $dataType = $this->resolveDataType($categoryName, $alias, $rawName);
                        $validity = $this->extractValidity($rawName, $alias);
                        $planName = $this->formatPlanName($rawName, $alias);

                        $amountAgent = $baseAmount;
                        $amountUser  = $baseAmount + 20.00;

                        // 1. Try to find existing plan with this payscribe_id
                        $plan = DataPlan::where('payscribe_id', $planCode)->first();

                        // 2. If not found, try to match existing plan by (network_key, data_type, plan_name, validity) where payscribe_id is null
                        if (!$plan) {
                            $plan = DataPlan::where('network_key', $networkKey)
                                ->where('data_type', $dataType)
                                ->where('plan_name', $planName)
                                ->where('validity', $validity)
                                ->whereNull('payscribe_id')
                                ->first();
                        }

                        // 3. Fallback match by (network_key, data_type, plan_name)
                        if (!$plan) {
                            $plan = DataPlan::where('network_key', $networkKey)
                                ->where('data_type', $dataType)
                                ->where('plan_name', $planName)
                                ->whereNull('payscribe_id')
                                ->first();
                        }

                        if ($plan) {
                            $plan->update([
                                'payscribe_id' => $planCode,
                                'plan_name'    => $planName,
                                'validity'     => $validity,
                                'amount'       => $plan->amount ?: $amountUser,
                                'amount_agent' => $plan->amount_agent ?: $amountAgent,
                                'enabled'      => true,
                            ]);
                            $totalUpdated++;
                        } else {
                            DataPlan::create([
                                'network_key'  => $networkKey,
                                'data_type'    => $dataType,
                                'plan_name'    => $planName,
                                'validity'     => $validity,
                                'payscribe_id' => $planCode,
                                'amount'       => $amountUser,
                                'amount_agent' => $amountAgent,
                                'enabled'      => true,
                                'sort_order'   => $sortOrder++,
                            ]);
                            $totalCreated++;
                        }
                    }
                }
            }
        }

        $this->command?->info("PayscribeDataSeeder completed: {$totalProcessed} processed ({$totalCreated} created, {$totalUpdated} updated).");
    }

    /**
     * Resolve VTU standard data_type from Payscribe category and alias.
     */
    private function resolveDataType(string $category, string $alias, string $name): string
    {
        $cat = strtolower($category);
        $al  = strtolower($alias);
        $nm  = strtolower($name);

        if (str_contains($cat, 'awoof') || str_contains($al, 'awoof') || str_contains($nm, 'awoof')) {
            return 'awoof';
        }
        if (str_contains($cat, 'corporate') || str_contains($al, 'corporate') || str_contains($nm, 'corporate') || str_contains($al, 'cg') || str_contains($nm, 'cg')) {
            return 'cg';
        }
        if (str_contains($cat, 'sme') || str_contains($al, 'sme') || str_contains($nm, 'sme')) {
            return 'sme';
        }
        if (str_contains($cat, 'gift') || str_contains($al, 'gift') || str_contains($nm, 'gift')) {
            return 'gifting';
        }
        if (str_contains($cat, 'data share') || str_contains($al, 'data_share') || str_contains($nm, 'data share')) {
            return 'sme';
        }

        return 'cg';
    }

    /**
     * Extract standardized validity from plan name and alias.
     */
    private function extractValidity(string $name, string $alias = ''): string
    {
        $combined = $name . ' ' . $alias;
        if (preg_match('/\b(365\s*days?|1\s*year|yearly)\b/i', $combined)) return '365 Days';
        if (preg_match('/\b(120\s*days?)\b/i', $combined)) return '120 Days';
        if (preg_match('/\b(90\s*days?|3\s*months?)\b/i', $combined)) return '90 Days';
        if (preg_match('/\b(60\s*days?|2\s*months?)\b/i', $combined)) return '60 Days';
        if (preg_match('/\b(30\s*days?|monthly|1\s*month)\b/i', $combined)) return '30 Days';
        if (preg_match('/\b(15\s*days?)\b/i', $combined)) return '15 Days';
        if (preg_match('/\b(14\s*days?|2\s*weeks?)\b/i', $combined)) return '14 Days';
        if (preg_match('/\b(10\s*days?)\b/i', $combined)) return '10 Days';
        if (preg_match('/\b(7\s*days?|weekly|1\s*week)\b/i', $combined)) return '7 Days';
        if (preg_match('/\b(5\s*days?)\b/i', $combined)) return '5 Days';
        if (preg_match('/\b(3\s*days?)\b/i', $combined)) return '3 Days';
        if (preg_match('/\b(2\s*days?)\b/i', $combined)) return '2 Days';
        if (preg_match('/\b(1\s*days?|daily|24\s*hrs?|24\s*hours?)\b/i', $combined)) return '1 Day';

        return '30 Days';
    }

    /**
     * Format clean plan name extracting just the data plan value (e.g. 110MB, 1.2GB, 1TB).
     */
    private function formatPlanName(string $name, string $alias = ''): string
    {
        // 1. Match size like 110MB, 1.2GB, 500MB, 1TB, 6.75GB, 150mb, 8gb
        if (preg_match('/(\d+(?:\.\d+)?)\s*(MB|GB|TB)\b/i', $name, $matches)) {
            return strtoupper($matches[1] . $matches[2]);
        }

        // 2. Fallback: check alias if not found in name (e.g. mtn_110mb1_day -> 110MB)
        if (preg_match('/(\d+(?:_\d+)?)\s*(mb|gb|tb)/i', $alias, $matches)) {
            $val = str_replace('_', '.', $matches[1]);
            return strtoupper($val . $matches[2]);
        }

        $clean = trim($name);
        $clean = preg_replace('/\s+/', ' ', $clean);
        return mb_substr($clean, 0, 100);
    }
}
