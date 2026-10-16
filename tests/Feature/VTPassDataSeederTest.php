<?php

namespace Tests\Feature;

use App\Models\DataPlan;
use Database\Seeders\VTPassDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VTPassDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_vtpass_data_seeder_seeds_all_four_networks(): void
    {
        $this->seed(VTPassDataSeeder::class);

        // Check MTN plans
        $mtnPlans = DataPlan::where('network_key', 'mtn')->whereNotNull('vtpass_id')->get();
        $this->assertGreaterThanOrEqual(40, $mtnPlans->count());

        // Check Glo plans (both SME and CG)
        $gloSme = DataPlan::where('network_key', 'glo')->where('data_type', 'sme')->whereNotNull('vtpass_id')->get();
        $gloCg  = DataPlan::where('network_key', 'glo')->where('data_type', 'cg')->whereNotNull('vtpass_id')->get();
        $this->assertNotEmpty($gloSme);
        $this->assertNotEmpty($gloCg);

        // Check Airtel plans (CG)
        $airtelPlans = DataPlan::where('network_key', 'airtel')->where('data_type', 'cg')->whereNotNull('vtpass_id')->get();
        $this->assertGreaterThanOrEqual(40, $airtelPlans->count());

        // Check 9mobile / Etisalat plans (CG)
        $etisalatPlans = DataPlan::where('network_key', 'etisalat')->where('data_type', 'cg')->whereNotNull('vtpass_id')->get();
        $this->assertGreaterThanOrEqual(10, $etisalatPlans->count());
    }

    public function test_vtpass_pricing_formula(): void
    {
        $this->seed(VTPassDataSeeder::class);

        $sampleCodes = [
            'mtn-10mb-100'  => 100.00,
            'mtn-230mb-200' => 200.00,
            'glo-dg-295'    => 330.00,
            'airt-50'       => 50.00,
            'eti-100'       => 100.00,
        ];

        foreach ($sampleCodes as $code => $expectedAgentPrice) {
            $plan = DataPlan::where('vtpass_id', $code)->first();
            $this->assertNotNull($plan, "Plan for {$code} should exist");
            $this->assertEqualsWithDelta($expectedAgentPrice, (float) $plan->amount_agent, 0.01, "Agent price for {$code}");
            $this->assertEqualsWithDelta($expectedAgentPrice + 20.00, (float) $plan->amount, 0.01, "User price for {$code}");
        }
    }

    public function test_seeder_is_idempotent_and_prevents_duplicate_plans(): void
    {
        // First run
        $this->seed(VTPassDataSeeder::class);
        $initialCount = DataPlan::whereNotNull('vtpass_id')->count();

        // Second run
        $this->seed(VTPassDataSeeder::class);
        $secondCount = DataPlan::whereNotNull('vtpass_id')->count();

        // Count must remain identical without duplicate rows
        $this->assertEquals($initialCount, $secondCount);
    }
}
