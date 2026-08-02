<?php

namespace Tests\Feature;

use App\Models\BatchIntake;
use App\Models\FishBatch;
use App\Models\FishSpecies;
use App\Models\PackageLabel;
use App\Models\ProcessingRecord;
use App\Models\ProcessingStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProcessorContractHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_processor_and_prediction_directories_enforce_bounded_pagination(): void
    {
        $this->seed();
        $processor = User::query()->where('email', 'processor@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($processor);
        $batch = FishBatch::query()
            ->whereHas('intakes', fn ($query) => $query->where('processor_organization_id', $processor->primaryOrganization()?->id))
            ->firstOrFail();

        foreach ([
            '/api/v1/processor/incoming-batches?per_page=101',
            '/api/v1/processor/history?per_page=101',
            "/api/v1/batches/{$batch->id}/children?per_page=101",
            "/api/v1/batches/{$batch->id}/ai-predictions?per_page=101",
        ] as $url) {
            $this->getJson($url)
                ->assertUnprocessable()
                ->assertJsonPath('error.code', 'VALIDATION_FAILED')
                ->assertJsonStructure(['error' => ['field_errors' => ['per_page']]]);
        }
    }

    public function test_step_route_binding_prevents_mutating_a_step_from_another_record(): void
    {
        $this->seed();
        $processor = User::query()->where('email', 'processor@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($processor);
        $authorizedRecord = ProcessingRecord::query()->where('organization_id', $processor->primaryOrganization()?->id)->firstOrFail();
        $authorizedRecord->update(['status' => 'IN_PROGRESS', 'completed_at' => null]);
        [$otherRecord, $otherStep] = $this->additionalRecord($processor, 'ACTIVE');

        $this->postJson("/api/v1/processing-records/{$authorizedRecord->id}/steps/{$otherStep->id}/complete", [
            'measurements' => ['cleaned_weight_kg' => 25],
        ])->assertNotFound();
        $this->assertDatabaseHas('processing_steps', ['id' => $otherStep->id, 'processing_record_id' => $otherRecord->id, 'status' => 'ACTIVE']);

        $otherStep->update(['status' => 'PENDING', 'started_at' => null, 'performed_by' => null]);
        $this->postJson("/api/v1/processing-records/{$authorizedRecord->id}/steps/{$otherStep->id}/start")->assertNotFound();
        $this->assertDatabaseHas('processing_steps', ['id' => $otherStep->id, 'processing_record_id' => $otherRecord->id, 'status' => 'PENDING']);
    }

    public function test_processing_record_notes_update_is_validated_locked_and_status_safe(): void
    {
        $this->seed();
        $processor = User::query()->where('email', 'processor@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($processor);
        $record = ProcessingRecord::query()->where('organization_id', $processor->primaryOrganization()?->id)->firstOrFail();
        $record->update(['status' => 'IN_PROGRESS', 'completed_at' => null]);

        $this->putJson("/api/v1/processing-records/{$record->id}", ['notes' => 'Packaging line verified.', 'status' => 'COMPLETED'])
            ->assertOk()
            ->assertJsonPath('data.notes', 'Packaging line verified.')
            ->assertJsonPath('data.status', 'IN_PROGRESS');
        $this->putJson("/api/v1/processing-records/{$record->id}", ['notes' => str_repeat('x', 2001)])->assertUnprocessable();

        $record->update(['status' => 'COMPLETED', 'completed_at' => now()]);
        $this->putJson("/api/v1/processing-records/{$record->id}", ['notes' => 'Too late'])->assertConflict();
    }

    public function test_completed_record_steps_cannot_be_reopened_or_recompleted(): void
    {
        $this->seed();
        $processor = User::query()->where('email', 'processor@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($processor);
        $record = ProcessingRecord::query()->where('organization_id', $processor->primaryOrganization()?->id)->where('status', 'COMPLETED')->firstOrFail();
        $step = $record->steps()->firstOrFail();

        $this->postJson("/api/v1/processing-records/{$record->id}/steps/{$step->id}/complete", [
            'measurements' => ['cleaned_weight_kg' => 98],
        ])->assertConflict();
        $this->assertDatabaseHas('processing_steps', ['id' => $step->id, 'status' => 'COMPLETED']);
    }

    public function test_print_preview_get_request_does_not_mutate_the_package_label(): void
    {
        $this->seed();
        $processor = User::query()->where('email', 'processor@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($processor);
        $label = PackageLabel::query()->whereHas('batch', fn ($query) => $query->where('organization_id', $processor->primaryOrganization()?->id))->firstOrFail();
        $this->assertNull($label->printed_at);

        $this->get("/api/v1/package-labels/{$label->id}/print")->assertOk();

        $this->assertDatabaseHas('package_labels', ['id' => $label->id, 'printed_at' => null]);
    }

    private function additionalRecord(User $processor, string $stepStatus): array
    {
        $fisherBatch = FishBatch::query()->create([
            'organization_id' => User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail()->primaryOrganization()?->id,
            'created_by' => User::query()->where('email', 'fisher@fishtrace.demo')->value('id'),
            'fish_species_id' => FishSpecies::query()->firstOrFail()->id,
            'batch_code' => 'FT-ROUTE-BINDING',
            'type' => 'RAW',
            'status' => 'PROCESSING',
            'product_type' => 'Chilled fish',
            'total_weight_kg' => 30,
            'created_from_catch_at' => now(),
        ]);
        $intake = BatchIntake::query()->create([
            'fish_batch_id' => $fisherBatch->id,
            'processor_organization_id' => $processor->primaryOrganization()?->id,
            'received_by' => $processor->id,
            'status' => 'ACCEPTED',
            'received_weight_kg' => 30,
            'received_at' => now(),
        ]);
        $record = ProcessingRecord::query()->create([
            'fish_batch_id' => $fisherBatch->id,
            'batch_intake_id' => $intake->id,
            'organization_id' => $processor->primaryOrganization()?->id,
            'created_by' => $processor->id,
            'status' => 'IN_PROGRESS',
            'input_weight_kg' => 30,
            'started_at' => now(),
        ]);
        $step = ProcessingStep::query()->create([
            'processing_record_id' => $record->id,
            'type' => 'CLEANING',
            'sequence' => 1,
            'status' => $stepStatus,
            'performed_by' => $stepStatus === 'ACTIVE' ? $processor->id : null,
            'started_at' => $stepStatus === 'ACTIVE' ? now() : null,
        ]);

        return [$record, $step];
    }
}
