<?php

namespace Tests\Feature;

use App\Models\FishSpecies;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FisherApiCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_fisher_dashboard_returns_bounded_personal_operational_totals(): void
    {
        $this->seed();
        $fisher = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($fisher);

        $this->getJson('/api/v1/fisher/dashboard')->assertOk()
            ->assertJsonPath('data.trip_counts.COMPLETED', 1)
            ->assertJsonPath('data.boats.total', 1)
            ->assertJsonPath('data.boats.active', 1)
            ->assertJsonPath('data.catches.count', 1)
            ->assertJsonPath('data.catches.total_weight_kg', 120)
            ->assertJsonPath('data.catches.allocated_weight_kg', 100)
            ->assertJsonPath('data.catches.available_weight_kg', 20)
            ->assertJsonPath('data.batch_count', 1)
            ->assertJsonCount(1, 'data.recent_trips');
    }

    public function test_draft_trip_can_be_edited_but_active_trip_and_other_fisher_cannot(): void
    {
        $this->seed();
        $fisher = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($fisher);
        $firstBoat = $this->postJson('/api/v1/boats', ['registration_number' => 'EDIT-BOAT-1', 'name' => 'First Boat', 'capacity_kg' => 200])->assertCreated()->json('data');
        $secondBoat = $this->postJson('/api/v1/boats', ['registration_number' => 'EDIT-BOAT-2', 'name' => 'Second Boat', 'capacity_kg' => 250])->assertCreated()->json('data');
        $trip = $this->postJson('/api/v1/fishing-trips', ['boat_id' => $firstBoat['id'], 'general_catch_area' => 'Original area'])->assertCreated()->json('data');

        $this->putJson('/api/v1/fishing-trips/'.$trip['id'], ['boat_id' => $secondBoat['id'], 'general_catch_area' => 'Updated area'])->assertOk()->assertJsonPath('data.boat_id', $secondBoat['id'])->assertJsonPath('data.general_catch_area', 'Updated area')->assertJsonPath('data.status', 'DRAFT');

        $other = $this->otherFisher();
        Sanctum::actingAs($other);
        $this->putJson('/api/v1/fishing-trips/'.$trip['id'], ['boat_id' => $secondBoat['id'], 'general_catch_area' => 'Forbidden'])->assertForbidden();

        Sanctum::actingAs($fisher);
        $this->postJson('/api/v1/fishing-trips/'.$trip['id'].'/start')->assertOk()->assertJsonPath('data.status', 'ACTIVE');
        $this->putJson('/api/v1/fishing-trips/'.$trip['id'], ['boat_id' => $secondBoat['id'], 'general_catch_area' => 'Too late'])->assertStatus(409);
        $this->postJson('/api/v1/fishing-trips/'.$trip['id'].'/start')->assertStatus(409);
    }

    public function test_active_catch_can_be_edited_with_immutable_offline_identity(): void
    {
        $this->seed();
        [$fisher, $trip, $catch] = $this->activeCatch('5af4d8a0-8973-40c4-a652-a15d6e06aa65');
        $species = FishSpecies::query()->firstOrFail();
        Sanctum::actingAs($fisher);
        $payload = ['fish_species_id' => $species->id, 'fishing_gear_type_id' => null, 'weight_kg' => 55, 'quantity' => 3, 'caught_at' => now()->subHour()->toIso8601String(), 'client_created_at' => now()->subHour()->toIso8601String()];

        $this->putJson('/api/v1/catches/'.$catch['id'], $payload)->assertOk()->assertJsonPath('data.weight_kg', '55.000')->assertJsonPath('data.quantity', 3)->assertJsonPath('data.client_record_id', '5af4d8a0-8973-40c4-a652-a15d6e06aa65')->assertJsonPath('data.fishing_trip_id', $trip['id']);
        $this->assertDatabaseHas('catch_records', ['id' => $catch['id'], 'client_record_id' => '5af4d8a0-8973-40c4-a652-a15d6e06aa65', 'fishing_trip_id' => $trip['id']]);

        $other = $this->otherFisher();
        Sanctum::actingAs($other);
        $this->putJson('/api/v1/catches/'.$catch['id'], $payload)->assertForbidden();
    }

    public function test_allocated_catch_cannot_shrink_change_species_or_be_deleted(): void
    {
        $this->seed();
        [$fisher, $trip, $catch] = $this->activeCatch();
        Sanctum::actingAs($fisher);
        $species = FishSpecies::query()->firstOrFail();
        $this->postJson('/api/v1/batches', ['fish_species_id' => $species->id, 'product_type' => 'Chilled fish', 'catches' => [['catch_id' => $catch['id'], 'weight_kg' => 30]]])->assertCreated();
        $payload = ['fish_species_id' => $species->id, 'fishing_gear_type_id' => null, 'weight_kg' => 29, 'quantity' => 2, 'caught_at' => now()->toIso8601String(), 'client_created_at' => null];
        $this->putJson('/api/v1/catches/'.$catch['id'], $payload)->assertStatus(422);

        $otherSpecies = FishSpecies::create(['common_name' => 'Skipjack Tuna', 'scientific_name' => 'Katsuwonus pelamis']);
        $payload['weight_kg'] = 40;
        $payload['fish_species_id'] = $otherSpecies->id;
        $this->putJson('/api/v1/catches/'.$catch['id'], $payload)->assertStatus(422);
        $this->deleteJson('/api/v1/catches/'.$catch['id'])->assertStatus(409);
        $this->assertDatabaseHas('catch_records', ['id' => $catch['id'], 'weight_kg' => 40, 'fish_species_id' => $species->id]);

        $payload['fish_species_id'] = $species->id;
        $payload['weight_kg'] = 35;
        $this->putJson('/api/v1/catches/'.$catch['id'], $payload)->assertOk()->assertJsonPath('data.weight_kg', '35.000');
        $this->postJson('/api/v1/fishing-trips/'.$trip['id'].'/complete')->assertOk();
        $payload['weight_kg'] = 36;
        $this->putJson('/api/v1/catches/'.$catch['id'], $payload)->assertStatus(409);
    }

    public function test_only_unallocated_catch_on_active_trip_can_be_deleted(): void
    {
        $this->seed();
        [$fisher, $trip, $catch] = $this->activeCatch();
        Sanctum::actingAs($fisher);
        $this->deleteJson('/api/v1/catches/'.$catch['id'])->assertNoContent();
        $this->assertDatabaseMissing('catch_records', ['id' => $catch['id']]);

        $species = FishSpecies::query()->firstOrFail();
        $second = $this->postJson('/api/v1/catches', ['fishing_trip_id' => $trip['id'], 'fish_species_id' => $species->id, 'weight_kg' => 10, 'quantity' => 1, 'caught_at' => now()->toIso8601String()])->assertCreated()->json('data');
        $this->postJson('/api/v1/fishing-trips/'.$trip['id'].'/complete')->assertOk();
        $this->deleteJson('/api/v1/catches/'.$second['id'])->assertStatus(409);
        $this->assertDatabaseHas('catch_records', ['id' => $second['id']]);
    }

    private function activeCatch(?string $clientRecordId = null): array
    {
        $fisher = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($fisher);
        $boat = $this->postJson('/api/v1/boats', ['registration_number' => 'ACTIVE-'.str()->upper(str()->random(8)), 'name' => 'Active Catch Boat', 'capacity_kg' => 400])->assertCreated()->json('data');
        $trip = $this->postJson('/api/v1/fishing-trips', ['boat_id' => $boat['id'], 'general_catch_area' => 'Southern waters'])->assertCreated()->json('data');
        $this->postJson('/api/v1/fishing-trips/'.$trip['id'].'/start')->assertOk();
        $species = FishSpecies::query()->firstOrFail();
        $catch = $this->postJson('/api/v1/catches', ['fishing_trip_id' => $trip['id'], 'fish_species_id' => $species->id, 'weight_kg' => 40, 'quantity' => 2, 'caught_at' => now()->toIso8601String(), 'client_record_id' => $clientRecordId])->assertCreated()->json('data');

        return [$fisher, $trip, $catch];
    }

    private function otherFisher(): User
    {
        $organization = Organization::query()->where('type', 'FISHER')->firstOrFail();
        $role = Role::query()->where('name', 'FISHER')->firstOrFail();
        $user = User::firstOrCreate(['email' => 'other.fisher@example.test'], ['name' => 'Other Fisher', 'password' => 'OtherFisher2026', 'status' => 'ACTIVE']);
        $user->roles()->syncWithoutDetaching([$role->id]);
        $user->organizations()->syncWithoutDetaching([$organization->id => ['is_primary' => true]]);

        return $user->fresh(['roles', 'organizations']);
    }
}
