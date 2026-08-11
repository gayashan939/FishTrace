<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BoatMobileContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_boat_payload_is_persisted_and_returned_with_exact_snake_case_keys(): void
    {
        $this->seed();
        Sanctum::actingAs(User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail());

        $payload = [
            'registration_number' => 'MOB-BOAT-001',
            'name' => 'Sea Light',
            'type' => 'LONG_LINER',
            'length_meters' => 9.8,
            'engine_details' => 'Inboard diesel',
            'home_port' => 'Kochi',
            'is_active' => true,
        ];

        $created = $this->postJson('/api/v1/boats', $payload)
            ->assertCreated()
            ->assertJsonPath('data.length_meters', '9.80')
            ->assertJsonPath('data.engine_details', 'Inboard diesel')
            ->assertJsonPath('data.home_port', 'Kochi')
            ->assertJsonStructure(['data' => [
                'id', 'registration_number', 'name', 'type', 'length_meters',
                'engine_details', 'home_port', 'is_active',
            ]])
            ->json('data');

        $this->putJson('/api/v1/boats/'.$created['id'], array_merge($payload, [
            'engine_details' => 'Inboard diesel V2',
        ]))
            ->assertOk()
            ->assertJsonPath('data.engine_details', 'Inboard diesel V2');
    }

    public function test_mobile_boat_contract_rejects_missing_or_invalid_required_values(): void
    {
        $this->seed();
        Sanctum::actingAs(User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail());

        $this->postJson('/api/v1/boats', [
            'registration_number' => 'MOB-BOAT-002',
            'name' => 'Incomplete Boat',
            'type' => 'NOT_A_BOAT_TYPE',
        ])->assertUnprocessable()->assertJsonStructure([
            'error' => ['field_errors' => ['type', 'length_meters', 'engine_details', 'home_port']],
        ]);
    }
}
