<?php

namespace Tests\Feature;

use App\Models\FishBatch;
use App\Models\FishSpecies;
use App\Models\TraceabilityEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FisherMilestoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_fisher_directories_are_bounded_and_batch_resources_exclude_private_fields(): void
    {
        $this->seed();
        $fisher = User::where('email', 'fisher@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($fisher);

        foreach (['boats', 'fishing-trips', 'catches', 'batches'] as $directory) {
            $this->getJson("/api/v1/{$directory}?per_page=101")
                ->assertUnprocessable()
                ->assertJsonPath('error.code', 'VALIDATION_FAILED')
                ->assertJsonStructure(['error' => ['field_errors' => ['per_page']]]);
        }

        $batch = FishBatch::query()->where('organization_id', $fisher->primaryOrganization()?->id)->firstOrFail();
        TraceabilityEvent::query()->create([
            'fish_batch_id' => $batch->id,
            'organization_id' => $fisher->primaryOrganization()?->id,
            'actor_id' => $fisher->id,
            'event_type' => 'PRIVATE_TEST_EVENT',
            'title' => 'Private contract check',
            'public_data' => ['visible' => true],
            'private_data' => ['secret' => 'fisher-private-event-value'],
            'occurred_at' => now(),
        ]);

        $this->getJson("/api/v1/batches/{$batch->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.qr_code.public_token')
            ->assertJsonMissingPath('data.events.0.private_data')
            ->assertDontSee('fisher-private-event-value');
        $this->getJson("/api/v1/batches/{$batch->id}/timeline")
            ->assertOk()
            ->assertDontSee('private_data')
            ->assertDontSee('fisher-private-event-value');
    }

    public function test_fisher_can_create_trip_catch_batch_and_qr(): void
    {
        $this->seed();
        $fisher = User::where('email', 'fisher@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($fisher);
        $org = $fisher->primaryOrganization();
        $boat = $this->postJson('/api/v1/boats', ['registration_number' => 'TEST-BOAT-1', 'name' => 'Test Vessel', 'capacity_kg' => 500])->assertCreated()->json('data');
        $trip = $this->postJson('/api/v1/fishing-trips', ['boat_id' => $boat['id'], 'general_catch_area' => 'Southern coastal waters'])->assertCreated()->json('data');
        $this->postJson("/api/v1/fishing-trips/{$trip['id']}/start")->assertOk()->assertJsonPath('data.status', 'ACTIVE');
        $species = FishSpecies::firstOrFail();
        $catch = $this->postJson('/api/v1/catches', ['fishing_trip_id' => $trip['id'], 'fish_species_id' => $species->id, 'weight_kg' => 40, 'quantity' => 2, 'caught_at' => now()->toIso8601String(), 'client_record_id' => '3f93f625-a13b-43db-aae1-06d8b822bf4d'])->assertCreated()->json('data');
        $duplicate = $this->postJson('/api/v1/catches', ['fishing_trip_id' => $trip['id'], 'fish_species_id' => $species->id, 'weight_kg' => 40, 'quantity' => 2, 'caught_at' => now()->toIso8601String(), 'client_record_id' => '3f93f625-a13b-43db-aae1-06d8b822bf4d'])->assertCreated()->json('data');
        $this->assertSame($catch['id'], $duplicate['id']);
        $batch = $this->postJson('/api/v1/batches', ['fish_species_id' => $species->id, 'product_type' => 'Chilled whole fish', 'catches' => [['catch_id' => $catch['id'], 'weight_kg' => 30]]])->assertCreated()->assertJsonPath('data.total_weight_kg', '30.000')->json('data');
        $this->get("/api/v1/batches/{$batch['id']}/qr")->assertOk()->assertHeader('content-type', 'image/svg+xml');
    }
}
