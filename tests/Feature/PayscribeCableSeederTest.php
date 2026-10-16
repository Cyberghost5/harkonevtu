<?php

namespace Tests\Feature;

use App\Models\CablePlan;
use App\Models\CableProvider;
use Database\Seeders\PayscribeCableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayscribeCableSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure cable providers exist
        CableProvider::firstOrCreate(['slug' => 'dstv'], [
            'name'          => 'DSTV',
            'easyaccess_id' => '01',
            'payscribe_id'  => 'dstv',
            'enabled'       => true,
            'sort_order'    => 1,
        ]);

        CableProvider::firstOrCreate(['slug' => 'gotv'], [
            'name'          => 'GOtv',
            'easyaccess_id' => '02',
            'payscribe_id'  => 'gotv',
            'enabled'       => true,
            'sort_order'    => 2,
        ]);

        CableProvider::firstOrCreate(['slug' => 'startimes'], [
            'name'          => 'Startimes',
            'easyaccess_id' => '03',
            'payscribe_id'  => 'startimes',
            'enabled'       => true,
            'sort_order'    => 3,
        ]);
    }

    public function test_payscribe_cable_seeder_seeds_all_three_providers(): void
    {
        $this->seed(PayscribeCableSeeder::class);

        $dstv = CableProvider::where('slug', 'dstv')->firstOrFail();
        $gotv = CableProvider::where('slug', 'gotv')->firstOrFail();
        $startimes = CableProvider::where('slug', 'startimes')->firstOrFail();

        $dstvPlans = CablePlan::where('cable_provider_id', $dstv->id)->whereNotNull('payscribe_id')->get();
        $gotvPlans = CablePlan::where('cable_provider_id', $gotv->id)->whereNotNull('payscribe_id')->get();
        $starPlans = CablePlan::where('cable_provider_id', $startimes->id)->whereNotNull('payscribe_id')->get();

        $this->assertGreaterThanOrEqual(30, $dstvPlans->count(), 'DStv should have at least 30 Payscribe plans');
        $this->assertGreaterThanOrEqual(10, $gotvPlans->count(), 'GOtv should have at least 10 Payscribe plans');
        $this->assertGreaterThanOrEqual(18, $starPlans->count(), 'Startimes should have at least 18 Payscribe plans');

        // Check specific key plans and their Payscribe IDs
        $gotvSmallie = CablePlan::where('cable_provider_id', $gotv->id)->where('name', 'like', '%Smallie%')->first();
        $this->assertNotNull($gotvSmallie);
        $this->assertEquals('UnQyMk1jejMrNlFPOFkrOHozRTIrZz09', $gotvSmallie->payscribe_id);
        $this->assertEquals(1900.00, $gotvSmallie->amount);

        $gotvJinja = CablePlan::where('cable_provider_id', $gotv->id)->where('name', 'like', '%Jinja%')->first();
        $this->assertNotNull($gotvJinja);
        $this->assertEquals('dGd6cEkyaWNiaGJ1Ykh5VzBCcjZvZz09', $gotvJinja->payscribe_id);
        $this->assertEquals(3900.00, $gotvJinja->amount);

        $starSmart = CablePlan::where('cable_provider_id', $startimes->id)->where('name', 'like', '%Smart%')->first();
        $this->assertNotNull($starSmart);
        $this->assertEquals('aFZHc1c3R2MzUnJqUVVaRkRRakZLUT09', $starSmart->payscribe_id);
        $this->assertEquals(4700.00, $starSmart->amount);
    }

    public function test_payscribe_cable_seeder_is_idempotent(): void
    {
        $this->seed(PayscribeCableSeeder::class);
        $countAfterFirst = CablePlan::count();

        // Seed second time
        $this->seed(PayscribeCableSeeder::class);
        $countAfterSecond = CablePlan::count();

        $this->assertEquals($countAfterFirst, $countAfterSecond, 'Seeder must be idempotent and not create duplicate records');
    }
}
