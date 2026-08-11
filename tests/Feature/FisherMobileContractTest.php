<?php

namespace Tests\Feature;

use App\Models\FishingGearType;
use App\Models\FishSpecies;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FisherMobileContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_trip_catch_and_batch_mobile_fields_are_persisted_and_returned(): void
    {
        $this->seed();
        $fisher = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($fisher);

        $boat = $this->postJson('/api/v1/boats', [
            'registration_number' => 'MOBILE-FISHER-001',
            'name' => 'Mobile Contract Boat',
            'type' => 'LONG_LINER',
            'length_meters' => 12.5,
            'engine_details' => 'Inboard diesel',
            'home_port' => 'Kochi',
        ])->assertCreated()->json('data');

        $tripClientId = '9cf53543-2582-4f4a-b62a-4c21e7cfc2a3';
        $trip = $this->postJson('/api/v1/fishing-trips', [
            'boat_id' => $boat['id'],
            'trip_code' => 'TRIP-2026-MOBILE-001',
            'client_record_id' => $tripClientId,
            'planned_departure_at' => '2026-08-08T01:00:00Z',
            'expected_duration_hours' => 12,
            'general_catch_area' => '17.6858, 83.2185',
            'fishing_area_latitude' => 17.6858,
            'fishing_area_longitude' => 83.2185,
            'crew' => ['Alex Johnson', 'M. Kumar'],
            'notes' => 'Mobile trip notes',
        ])->assertCreated()
            ->assertJsonPath('data.trip_code', 'TRIP-2026-MOBILE-001')
            ->assertJsonPath('data.client_record_id', $tripClientId)
            ->assertJsonPath('data.crew.0', 'Alex Johnson')
            ->assertJsonPath('data.expected_duration_hours', '12.00')
            ->json('data');

        $replay = $this->postJson('/api/v1/fishing-trips', [
            'boat_id' => $boat['id'],
            'trip_code' => 'TRIP-2026-MOBILE-REPLAY',
            'client_record_id' => $tripClientId,
        ])->assertCreated()->json('data');
        $this->assertSame($trip['id'], $replay['id']);

        $this->postJson('/api/v1/fishing-trips/'.$trip['id'].'/start')->assertOk()->assertJsonPath('data.status', 'ACTIVE');

        $species = FishSpecies::query()->where('is_active', true)->firstOrFail();
        $gear = FishingGearType::query()->where('is_active', true)->firstOrFail();
        $catch = $this->postJson('/api/v1/catches', [
            'fishing_trip_id' => $trip['id'],
            'fish_species_id' => $species->id,
            'fishing_gear_type_id' => $gear->id,
            'weight_kg' => 40,
            'quantity' => 2,
            'condition' => 'GOOD',
            'latitude' => 17.686,
            'longitude' => 83.219,
            'notes' => 'Mobile catch notes',
            'caught_at' => '2026-08-08T02:00:00Z',
            'client_record_id' => 'f559c1c1-915f-4870-a08f-22be0e6e4e35',
        ])->assertCreated()
            ->assertJsonPath('data.condition', 'GOOD')
            ->assertJsonPath('data.latitude', '17.6860000')
            ->assertJsonPath('data.gear.id', $gear->id)
            ->assertJsonPath('data.verified', true)
            ->json('data');

        $this->postJson('/api/v1/batches', [
            'fish_species_id' => $species->id,
            'product_type' => 'WHOLE',
            'quality_grade' => 'A',
            'storage_temperature_celsius' => 2,
            'ice_type' => 'FLAKE_ICE',
            'ice_amount_kg' => 25,
            'landing_site_name' => 'Kochi Port',
            'notes' => 'Mobile batch notes',
            'catches' => [['catch_id' => $catch['id'], 'weight_kg' => 40]],
        ])->assertCreated()
            ->assertJsonPath('data.fishing_trip_id', $trip['id'])
            ->assertJsonPath('data.fish_count', 2)
            ->assertJsonPath('data.quality_grade', 'A')
            ->assertJsonPath('data.storage_temperature_celsius', '2.00')
            ->assertJsonPath('data.ice_type', 'FLAKE_ICE')
            ->assertJsonPath('data.landing_site_name', 'Kochi Port');
    }

    public function test_active_trip_directory_honours_mobile_status_and_sort_filters(): void
    {
        $this->seed();
        Sanctum::actingAs(User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail());

        $this->getJson('/api/v1/fishing-trips?status=ACTIVE&sort=departed_at&direction=desc&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.current_page', 1);
    }
}
