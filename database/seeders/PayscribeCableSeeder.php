<?php

namespace Database\Seeders;

use App\Models\CablePlan;
use App\Models\CableProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PayscribeCableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dstvFile      = base_path('docs/payscribe-cable/dstv.json');
        $gotvFile      = base_path('docs/payscribe-cable/gotv.json');
        $startimesFile = base_path('docs/payscribe-cable/startimes.json');

        if (!File::exists($dstvFile) && !File::exists($gotvFile) && !File::exists($startimesFile)) {
            $this->command?->warn('No Payscribe cable JSON files found in docs/payscribe-cable.');
            return;
        }

        $dstvRaw      = File::exists($dstvFile) ? (json_decode(File::get($dstvFile), true)['message']['details'] ?? []) : [];
        $gotvRaw      = File::exists($gotvFile) ? (json_decode(File::get($gotvFile), true)['message']['details'] ?? []) : [];
        $startimesRaw = File::exists($startimesFile) ? (json_decode(File::get($startimesFile), true)['message']['details'] ?? []) : [];

        // 1. DStv items (merge dstv.json + any dstv items found in startimes.json)
        $dstvPlans = [];
        foreach ($dstvRaw as $item) {
            $alias = $item['alias'] ?? '';
            if (str_starts_with($alias, 'ng_dstv') && !isset($dstvPlans[$alias])) {
                $dstvPlans[$alias] = $item;
            }
        }
        foreach ($startimesRaw as $item) {
            $alias = $item['alias'] ?? '';
            if (str_starts_with($alias, 'ng_dstv') && !isset($dstvPlans[$alias])) {
                $dstvPlans[$alias] = $item;
            }
        }

        // 2. GOtv items
        $gotvPlans = [];
        foreach ($gotvRaw as $item) {
            $alias = $item['alias'] ?? '';
            if (str_starts_with($alias, 'ng_gotv') && !isset($gotvPlans[$alias])) {
                $gotvPlans[$alias] = $item;
            }
        }

        // 3. Startimes items (excluding any DStv items)
        $startimesPlans = [];
        foreach ($startimesRaw as $item) {
            $alias = $item['alias'] ?? '';
            if ($alias && !str_starts_with($alias, 'ng_dstv') && !isset($startimesPlans[$alias])) {
                $startimesPlans[$alias] = $item;
            }
        }

        $providers = [
            'dstv'      => ['name' => 'DStv', 'plans' => $dstvPlans],
            'gotv'      => ['name' => 'GOtv', 'plans' => $gotvPlans],
            'startimes' => ['name' => 'Startimes', 'plans' => $startimesPlans],
        ];

        $totalUpdated = 0;
        $totalCreated = 0;

        foreach ($providers as $slug => $data) {
            $provider = CableProvider::where('slug', $slug)->first();
            if (!$provider) {
                $this->command?->warn("Cable provider '{$slug}' not found in database. Skipping.");
                continue;
            }

            $this->command?->info("Processing {$data['name']} (" . count($data['plans']) . " unique bouquets)...");

            $dbPlans      = CablePlan::where('cable_provider_id', $provider->id)->get();
            $maxSortOrder = (int) CablePlan::where('cable_provider_id', $provider->id)->max('sort_order');
            $sortOrder    = $maxSortOrder + 1;
            $matchedDbIds = [];

            foreach ($data['plans'] as $alias => $psItem) {
                $psId     = trim((string) ($psItem['id'] ?? ''));
                $rawName  = trim((string) ($psItem['name'] ?? ''));
                $psAmount = (float) ($psItem['amount'] ?? 0);

                if ($psId === '' || $psAmount <= 0) {
                    continue;
                }

                // 1. Try to match existing DB plan
                $match = $dbPlans->first(function (CablePlan $p) use ($slug, $alias, $psId, $rawName, $matchedDbIds) {
                    if (in_array($p->id, $matchedDbIds)) {
                        return false;
                    }

                    // Direct payscribe_id match
                    if ($p->payscribe_id && $p->payscribe_id === $psId) {
                        return true;
                    }

                    $pName = strtolower(trim($p->name));
                    $vt    = strtolower(trim($p->vtpass_id ?? ''));
                    $al    = strtolower(trim($alias));

                    // DStv known mappings
                    if ($slug === 'dstv') {
                        if ($al === 'ng_dstv_nltese36' && ($vt === 'padi' || str_contains($pName, 'padi'))) return true;
                        if ($al === 'ng_dstv_nnj1e36' && ($vt === 'yanga' || $pName === 'dstv yanga')) return true;
                        if ($al === 'ng_dstv_nnj2e36' && ($vt === 'confam' || $pName === 'dstv confam')) return true;
                        if ($al === 'ng_dstv_compe36' && ($vt === 'compact' || $pName === 'dstv compact')) return true;
                        if ($al === 'ng_dstv_comple36' && ($vt === 'compact1' || $pName === 'dstv compact plus')) return true;
                        if ($al === 'ng_dstv_prwe36' && ($vt === 'premium' || $pName === 'dstv premium')) return true;
                    }

                    // GOtv known mappings
                    if ($slug === 'gotv') {
                        if ($al === 'ng_gotv_gohan' && ($vt === 'gotv-smallie' || $pName === 'gotv smallie')) return true;
                        if ($al === 'ng_gotv_gotvnj1' && ($vt === 'gotv-jinja' || $pName === 'gotv jinja')) return true;
                        if ($al === 'ng_gotv_gotvnj2' && ($vt === 'gotv-jolli' || $pName === 'gotv jolli')) return true;
                        if ($al === 'ng_gotv_gotvmax' && ($vt === 'gotv-max' || $pName === 'gotv max')) return true;
                        if ($al === 'ng_gotv_gotvsupa' && ($vt === 'gotv-supa' || $pName === 'gotv supa')) return true;
                        if ($al === 'ng_gotv_gotvsupaplus' && ($vt === 'gotv-supa-plus' || $pName === 'gotv supa plus')) return true;
                        if ($al === 'ng_gotv_golite' && ($vt === 'gotv-smallie-quarterly' || str_contains($pName, 'quarterly'))) return true;
                        if ($al === 'ng_gotv_goltanl' && ($vt === 'gotv-smallie-yearly' || str_contains($pName, 'yearly'))) return true;
                    }

                    // Startimes known mappings
                    if ($slug === 'startimes') {
                        if ($al === 'nova' && ($vt === 'nova' || $pName === 'startimes nova')) return true;
                        if ($al === 'basic' && ($vt === 'basic' || $pName === 'startimes basic')) return true;
                        if ($al === 'smart' && ($vt === 'smart' || $pName === 'startimes smart')) return true;
                        if ($al === 'classic' && ($vt === 'classic' || $pName === 'startimes classic')) return true;
                        if ($al === 'super' && ($vt === 'super' || $pName === 'startimes super')) return true;
                    }

                    return false;
                });

                if ($match) {
                    $matchedDbIds[] = $match->id;
                    $match->update([
                        'payscribe_id' => $psId,
                        'amount'       => $psAmount,
                        'enabled'      => true,
                    ]);
                    $totalUpdated++;
                } else {
                    $formattedName = $this->formatPlanName($slug, $rawName, $alias);

                    CablePlan::create([
                        'cable_provider_id' => $provider->id,
                        'name'              => $formattedName,
                        'vtpass_id'         => $alias,
                        'payscribe_id'      => $psId,
                        'amount'            => $psAmount,
                        'enabled'           => true,
                        'sort_order'        => $sortOrder++,
                    ]);
                    $totalCreated++;
                }
            }
        }

        $this->command?->info("PayscribeCableSeeder finished: {$totalUpdated} updated, {$totalCreated} created.");
    }

    /**
     * Format display name for new cable plans.
     */
    private function formatPlanName(string $slug, string $rawName, string $alias): string
    {
        $name = preg_replace('/\s+/', ' ', trim($rawName));

        // Provider-specific adjustments
        if ($slug === 'dstv') {
            if ($alias === 'ng_dstv_showprwasie36') {
                return 'DStv Premium W/Afr + Showmax';
            }
            if ($alias === 'ng_dstv_showasiae36') {
                return 'DStv Asian + Showmax';
            }
            if ($alias === 'ng_dstv_showgwalle36') {
                return 'DStv Great Wall + Showmax';
            }
            if ($alias === 'ng_dstv_hdprme36') {
                return 'DStv Premium HD';
            }
            if ($alias === 'ng_dstv_mobmaxi') {
                return 'DStv Mobile';
            }
            if (!str_starts_with(strtolower($name), 'dstv')) {
                $name = 'DStv ' . $name;
            }
        } elseif ($slug === 'gotv') {
            if ($alias === 'ng_gotv_gosupaplm') {
                return 'GOtv Supa Plus (Monthly Promo)';
            }
            if (!str_starts_with(strtolower($name), 'gotv')) {
                $name = 'GOtv ' . $name;
            }
        } elseif ($slug === 'startimes') {
            if (!str_starts_with(strtolower($name), 'startimes')) {
                $name = 'Startimes ' . $name;
            }
        }

        return $name;
    }
}
