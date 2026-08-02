<?php

namespace Tests\Feature;

use App\Models\FishingGearType;
use App\Models\FishSpecies;
use App\Models\LandingSite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FisherReferenceDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_fisher_receives_only_active_operational_reference_values(): void
    {
        $this->seed();
        $inactiveSpecies = FishSpecies::query()->create(['common_name' => 'Inactive species', 'is_active' => false]);
        $inactiveGear = FishingGearType::query()->create(['name' => 'Inactive gear', 'is_active' => false]);
        $inactiveSite = LandingSite::query()->create(['name' => 'Inactive site', 'district' => 'Matara', 'is_active' => false]);
        Sanctum::actingAs(User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail());

        $response = $this->getJson('/api/v1/fisher/reference-data')->assertOk()
            ->assertJsonStructure(['data' => ['species' => [['id', 'common_name', 'scientific_name']], 'gear_types' => [['id', 'name']], 'landing_sites' => [['id', 'name', 'district']]], 'meta' => ['request_id']]);

        $this->assertNotContains($inactiveSpecies->id, collect($response->json('data.species'))->pluck('id')->all());
        $this->assertNotContains($inactiveGear->id, collect($response->json('data.gear_types'))->pluck('id')->all());
        $this->assertNotContains($inactiveSite->id, collect($response->json('data.landing_sites'))->pluck('id')->all());
    }

    public function test_non_fisher_cannot_read_fisher_reference_data(): void
    {
        $this->seed();
        Sanctum::actingAs(User::query()->where('email', 'retailer@fishtrace.demo')->firstOrFail());

        $this->getJson('/api/v1/fisher/reference-data')->assertForbidden();
    }
}
