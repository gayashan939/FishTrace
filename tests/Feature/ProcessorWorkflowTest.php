<?php

namespace Tests\Feature;

use App\Enums\BatchStatus;
use App\Models\FishBatch;
use App\Models\FishSpecies;
use App\Models\Organization;
use App\Models\QrCode;
use App\Models\Role;
use App\Models\TraceabilityEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProcessorWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_nested_batch_responses_do_not_expose_trace_tokens_or_private_event_data(): void
    {
        $this->seed();
        $processor = User::where('email', 'processor@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($processor);
        $batch = $this->availableBatch();
        QrCode::query()->create(['fish_batch_id' => $batch->id, 'public_token' => 'private-qr-token']);
        TraceabilityEvent::query()->create([
            'fish_batch_id' => $batch->id,
            'organization_id' => $processor->primaryOrganization()?->id,
            'actor_id' => $processor->id,
            'event_type' => 'PROCESSOR_REVIEWED',
            'title' => 'Processor reviewed batch',
            'public_data' => ['result' => 'accepted'],
            'private_data' => ['supplier_note' => 'private-event-secret'],
            'occurred_at' => now(),
        ]);

        $this->getJson("/api/v1/processor/batches/{$batch->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.qr_code.public_token')
            ->assertJsonMissingPath('data.events.0.private_data')
            ->assertDontSee('private-qr-token')
            ->assertDontSee('private-event-secret');

        $this->getJson('/api/v1/processor/incoming-batches')
            ->assertOk()
            ->assertDontSee('private-qr-token')
            ->assertDontSee('public_token');
    }

    public function test_processor_accepts_and_completes_ordered_processing_inspection_and_split(): void
    {
        $this->seed();
        $processor = User::where('email', 'processor@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($processor);
        $batch = $this->availableBatch();

        $this->getJson('/api/v1/processor/incoming-batches')->assertOk()->assertJsonFragment(['batch_code' => $batch->batch_code]);
        $this->postJson("/api/v1/processor/batches/{$batch->id}/accept", ['received_weight_kg' => 48])->assertCreated()->assertJsonPath('data.status', 'ACCEPTED');
        $record = $this->postJson('/api/v1/processing-records', ['fish_batch_id' => $batch->id, 'input_weight_kg' => 48])->assertCreated()->assertJsonPath('data.status', 'IN_PROGRESS')->json('data');
        $steps = collect($record['steps'])->keyBy('type');

        $this->postJson("/api/v1/processing-records/{$record['id']}/steps/{$steps['GRADING']['id']}/start")->assertConflict();
        $measurements = [
            'CLEANING' => ['cleaned_weight_kg' => 47],
            'GRADING' => ['grade' => 'A'],
            'FREEZING' => ['product_temperature' => -2.5],
            'PACKAGING' => ['output_weight_kg' => 44, 'waste_weight_kg' => 4, 'package_count' => 4],
        ];
        foreach (['CLEANING', 'GRADING', 'FREEZING', 'PACKAGING'] as $type) {
            $stepId = $steps[$type]['id'];
            $this->postJson("/api/v1/processing-records/{$record['id']}/steps/{$stepId}/start")->assertOk()->assertJsonPath('data.status', 'ACTIVE');
            $this->postJson("/api/v1/processing-records/{$record['id']}/steps/{$stepId}/complete", ['measurements' => $measurements[$type]])->assertOk()->assertJsonPath('data.status', 'COMPLETED');
        }

        $gradeId = \DB::table('quality_grades')->value('id');
        $inspection = $this->postJson('/api/v1/quality-inspections', ['processing_record_id' => $record['id'], 'result' => 'PASSED', 'quality_grade_id' => $gradeId, 'product_temperature' => -1.8, 'ph_level' => 5.9, 'appearance' => 'Bright and firm', 'odor' => 'Fresh'])->assertCreated()->assertJsonPath('data.result', 'PASSED');
        $this->assertDatabaseHas('processing_records', ['id' => $record['id'], 'status' => 'COMPLETED']);
        $this->assertDatabaseHas('fish_batches', ['id' => $batch->id, 'status' => 'PROCESSED', 'total_weight_kg' => 44]);

        $children = $this->postJson("/api/v1/batches/{$batch->id}/split", ['children' => [['weight_kg' => 20, 'package_count' => 2], ['weight_kg' => 24, 'product_type' => 'Frozen tuna loin', 'package_count' => 3]]])->assertCreated()->json('data');
        $this->assertCount(2, $children);
        $childIds = collect($children)->pluck('id');
        $this->assertSame(2, \DB::table('child_batches')->where('parent_batch_id', $batch->id)->count());
        $this->assertSame(2, \DB::table('package_labels')->whereIn('fish_batch_id', $childIds)->count());
        $this->assertSame(2, \DB::table('qr_codes')->whereIn('fish_batch_id', $childIds)->count());
        $labelId = \DB::table('package_labels')->where('fish_batch_id', $children[0]['id'])->value('id');
        $this->getJson("/api/v1/package-labels/{$labelId}")->assertOk()->assertJsonMissingPath('data.public_token');
        $this->get("/api/v1/package-labels/{$labelId}/print")->assertOk()->assertSee('FishTrace verified package');
        $this->getJson("/api/v1/batches/{$batch->id}/children")->assertOk()->assertJsonCount(2, 'data.data');
        $this->postJson("/api/v1/batches/{$batch->id}/split", ['children' => [['weight_kg' => 1, 'package_count' => 1], ['weight_kg' => 1, 'package_count' => 1]]])->assertUnprocessable();
    }

    public function test_rejection_requires_reason_and_does_not_claim_batch(): void
    {
        $this->seed();
        Sanctum::actingAs(User::where('email', 'processor@fishtrace.demo')->firstOrFail());
        $batch = $this->availableBatch();
        $this->postJson("/api/v1/processor/batches/{$batch->id}/reject", [])->assertUnprocessable();
        $this->postJson("/api/v1/processor/batches/{$batch->id}/reject", ['rejection_reason' => 'Temperature on arrival exceeded intake policy.'])->assertCreated()->assertJsonPath('data.status', 'REJECTED');
        $this->assertDatabaseHas('fish_batches', ['id' => $batch->id, 'status' => 'AVAILABLE_FOR_PROCESSING']);
    }

    public function test_second_processor_organization_cannot_access_claimed_batch(): void
    {
        $this->seed();
        $first = User::where('email', 'processor@fishtrace.demo')->firstOrFail();
        $batch = $this->availableBatch();
        Sanctum::actingAs($first);
        $this->postJson("/api/v1/processor/batches/{$batch->id}/accept", ['received_weight_kg' => 48])->assertCreated();
        $organization = Organization::create(['name' => 'Competing Processor', 'code' => 'CP-002', 'type' => 'PROCESSOR']);
        $other = User::create(['name' => 'Other Processor', 'email' => 'other-processor@example.test', 'password' => Hash::make('Password1234'), 'status' => 'ACTIVE']);
        $other->roles()->attach(Role::where('name', 'PROCESSOR')->firstOrFail());
        $other->organizations()->attach($organization, ['is_primary' => true]);
        Sanctum::actingAs($other);
        $this->getJson("/api/v1/processor/batches/{$batch->id}")->assertForbidden();
        $this->postJson('/api/v1/processing-records', ['fish_batch_id' => $batch->id, 'input_weight_kg' => 40])->assertForbidden();
    }

    private function availableBatch(): FishBatch
    {
        $fisher = User::where('email', 'fisher@fishtrace.demo')->firstOrFail();

        return FishBatch::create(['organization_id' => $fisher->primaryOrganization()?->id, 'created_by' => $fisher->id, 'fish_species_id' => FishSpecies::firstOrFail()->id, 'batch_code' => 'FT-PROCESSOR-'.str()->upper(str()->random(6)), 'type' => 'RAW', 'status' => BatchStatus::AVAILABLE_FOR_PROCESSING, 'product_type' => 'Chilled whole tuna', 'total_weight_kg' => 50, 'created_from_catch_at' => now()->subHours(8)]);
    }
}
