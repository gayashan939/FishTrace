<?php

namespace Tests\Feature;

use App\Models\FileAsset;
use App\Models\FishBatch;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FisherBatchDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_fisher_can_upload_and_list_private_batch_documents(): void
    {
        Storage::fake('local');
        $this->seed();
        $fisher = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $batch = FishBatch::query()->where('organization_id', $fisher->primaryOrganization()?->id)->firstOrFail();
        Sanctum::actingAs($fisher);

        $response = $this->post("/api/v1/batches/{$batch->id}/documents", [
            'file' => UploadedFile::fake()->create('landing-certificate.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonPath('data.category', 'BATCH_DOCUMENT')
            ->assertJsonPath('data.entity_id', $batch->id)
            ->assertJsonMissingPath('data.path')
            ->assertJsonMissingPath('data.disk');

        $asset = FileAsset::query()->findOrFail($response->json('data.id'));
        Storage::disk('local')->assertExists($asset->path);

        $this->getJson("/api/v1/batches/{$batch->id}/documents")
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $asset->id)
            ->assertJsonPath('data.data.0.download_url', route('files.show', $asset));
    }

    public function test_certificate_category_is_supported_but_unsafe_file_types_are_rejected(): void
    {
        Storage::fake('local');
        $this->seed();
        $fisher = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $batch = FishBatch::query()->where('organization_id', $fisher->primaryOrganization()?->id)->firstOrFail();
        Sanctum::actingAs($fisher);

        $this->post("/api/v1/batches/{$batch->id}/documents", [
            'category' => 'CERTIFICATE',
            'file' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.category', 'CERTIFICATE');

        $this->post("/api/v1/batches/{$batch->id}/documents", [
            'file' => UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
        ], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->assertDatabaseCount('file_assets', 1);
    }

    public function test_non_fisher_and_cross_organization_users_cannot_upload_batch_documents(): void
    {
        Storage::fake('local');
        $this->seed();
        $batch = FishBatch::query()->firstOrFail();
        $processor = User::query()->where('email', 'processor@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($processor);

        $this->post("/api/v1/batches/{$batch->id}/documents", [
            'file' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertForbidden();

        $organization = Organization::query()->create(['name' => 'Northern Fisher Cooperative', 'code' => 'NFC-TEST', 'type' => 'FISHER']);
        $otherFisher = User::query()->create(['name' => 'Other Fisher', 'email' => 'other-fisher@example.test', 'password' => 'FisherPassword2026', 'status' => 'ACTIVE']);
        $otherFisher->roles()->attach(Role::query()->where('name', 'FISHER')->firstOrFail());
        $otherFisher->organizations()->attach($organization, ['is_primary' => true]);
        Sanctum::actingAs($otherFisher->fresh(['roles', 'organizations']));

        $this->post("/api/v1/batches/{$batch->id}/documents", [
            'file' => UploadedFile::fake()->create('cross-organization.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertForbidden();

        $this->assertDatabaseCount('file_assets', 0);
    }
}
