<?php

namespace Tests\Feature;

use App\Models\DataPlan;
use Database\Seeders\PayscribeDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayscribeDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_payscribe_data_seeder_extracts_only_plan_value_as_plan_name(): void
    {
        $this->seed(PayscribeDataSeeder::class);

        $plans = DataPlan::whereNotNull('payscribe_id')->get();
        $this->assertGreaterThanOrEqual(100, $plans->count());

        // Check specific plan mentioned by user: PSPLAN_5367 (MTN 110MB -1DAYS-awoof)
        $p5367 = DataPlan::where('payscribe_id', 'PSPLAN_5367')->first();
        $this->assertNotNull($p5367);
        $this->assertEquals('110MB', $p5367->plan_name);
        $this->assertEquals('1 Day', $p5367->validity);
        $this->assertEquals('awoof', $p5367->data_type);

        // Check another plan: PSPLAN_5373 (MTN 1.2GB All Social -30DAYS -awoof)
        $p5373 = DataPlan::where('payscribe_id', 'PSPLAN_5373')->first();
        $this->assertNotNull($p5373);
        $this->assertEquals('1.2GB', $p5373->plan_name);

        // Verify that EVERY plan name is just a clean data value (e.g. 110MB, 500MB, 1.2GB, 1TB)
        foreach ($plans as $plan) {
            $this->assertMatchesRegularExpression(
                '/^\d+(\.\d+)?(MB|GB|TB)$/',
                $plan->plan_name,
                "Plan ID {$plan->id} has unexpected plan_name '{$plan->plan_name}'"
            );
        }
    }

    public function test_payscribe_seeder_is_idempotent(): void
    {
        $this->seed(PayscribeDataSeeder::class);
        $initialCount = DataPlan::whereNotNull('payscribe_id')->count();

        $this->seed(PayscribeDataSeeder::class);
        $secondCount = DataPlan::whereNotNull('payscribe_id')->count();

        $this->assertEquals($initialCount, $secondCount);
    }
}
