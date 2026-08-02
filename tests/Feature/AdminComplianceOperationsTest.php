<?php

namespace Tests\Feature;

use App\Actions\Retail\AdjustRetailInventory;
use App\Enums\InventoryStatus;
use App\Enums\NotificationType;
use App\Models\AIServiceFailure;
use App\Models\AuditLog;
use App\Models\BlockchainVerification;
use App\Models\FileAsset;
use App\Models\FishBatch;
use App\Models\InventoryLot;
use App\Models\QualityInspection;
use App\Models\ReportExport;
use App\Models\TraceabilityEvent;
use App\Models\User;
use App\Services\AI\SpoilagePredictionService;
use App\Services\Blockchain\TraceabilityAnchorService;
use App\Services\Notifications\OperationalNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminComplianceOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_review_quality_incidents_recalls_and_exports(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $inspector = User::query()->where('email', 'inspector@fishtrace.demo')->firstOrFail();
        $retailer = User::query()->where('email', 'retailer@fishtrace.demo')->firstOrFail();
        $passed = QualityInspection::query()->firstOrFail();
        $incident = QualityInspection::create(['fish_batch_id' => $passed->fish_batch_id, 'processing_record_id' => $passed->processing_record_id, 'organization_id' => $passed->organization_id, 'inspector_id' => $inspector->id, 'quality_grade_id' => null, 'result' => 'FAILED', 'product_temperature' => 6.5, 'ph_level' => 6.7, 'appearance' => 'Discoloration observed', 'odor' => 'Strong odor', 'notes' => 'Hold and investigate.', 'inspected_at' => now()]);
        $lot = InventoryLot::query()->firstOrFail();
        app(AdjustRetailInventory::class)->markUnavailable($retailer, $lot, InventoryStatus::RECALLED, 'Compliance recall test.');

        $this->actingAs($admin)->get('/admin/compliance/incidents')->assertOk()->assertSee('FT-DEMO-0001')->assertSee('FAILED')->assertDontSee('PASSED');
        $this->actingAs($admin)->get('/admin/compliance/incidents/'.$incident->id)->assertOk()->assertSee('Discoloration observed')->assertSee('Strong odor')->assertSee('LBL-DEMO-RETAIL-01');
        $this->actingAs($admin)->get('/admin/compliance/recalls')->assertOk()->assertSee('FT-DEMO-0001-01')->assertSee('Ocean Fresh Markets')->assertSee('LBL-DEMO-RETAIL-01');
        $incidents = $this->actingAs($admin)->get('/admin/compliance/incidents/export?result=FAILED');
        $incidents->assertOk();
        $this->assertStringContainsString('Discoloration observed', $incidents->streamedContent());
        $recalls = $this->actingAs($admin)->get('/admin/compliance/recalls/export');
        $recalls->assertOk();
        $this->assertStringContainsString('FT-DEMO-0001-01', $recalls->streamedContent());
        $this->assertTrue(AuditLog::query()->where('action', 'COMPLIANCE_INCIDENTS_EXPORTED')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'COMPLIANCE_RECALLS_EXPORTED')->exists());
    }

    public function test_private_evidence_is_listed_safely_and_downloaded_with_audit(): void
    {
        Storage::fake('local');
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $processor = User::query()->where('email', 'processor@fishtrace.demo')->firstOrFail();
        $inspection = QualityInspection::query()->firstOrFail();
        Storage::disk('local')->put('private/evidence/photo.png', 'private-evidence-body');
        $file = FileAsset::create(['organization_id' => $processor->primaryOrganization()?->id, 'uploaded_by' => $processor->id, 'category' => 'INSPECTION_IMAGE', 'entity_type' => 'quality_inspection', 'entity_id' => $inspection->id, 'disk' => 'local', 'path' => 'private/evidence/photo.png', 'original_name' => 'inspection-evidence.png', 'mime_type' => 'image/png', 'extension' => 'png', 'size_bytes' => 21, 'sha256' => hash('sha256', 'private-evidence-body')]);

        $this->actingAs($admin)->get('/admin/compliance/evidence')->assertOk()->assertSee('inspection-evidence.png')->assertDontSee('private/evidence/photo.png')->assertDontSee($file->sha256);
        $this->actingAs($admin)->get('/admin/compliance/evidence/'.$file->id.'/download')->assertOk()->assertHeader('content-type', 'image/png');
        $this->assertTrue(AuditLog::query()->where('action', 'ADMIN_EVIDENCE_DOWNLOADED')->where('auditable_id', $file->id)->exists());
    }

    public function test_notification_and_report_pages_exclude_context_filters_and_failure_bodies(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $retailer = User::query()->where('email', 'retailer@fishtrace.demo')->firstOrFail();
        app(OperationalNotifier::class)->user($retailer, NotificationType::RECALL, 'Compliance notice', 'Review affected inventory.', ['secret_context' => 'context-must-not-render']);
        ReportExport::create(['organization_id' => $retailer->primaryOrganization()?->id, 'requested_by' => $retailer->id, 'report_type' => 'recalls', 'filters' => ['secret' => 'filter-must-not-render'], 'status' => 'FAILED', 'failure_message' => 'failure-body-must-not-render']);

        $this->actingAs($admin)->get('/admin/compliance/notifications')->assertOk()->assertSee('Compliance notice')->assertSee('Review affected inventory.')->assertDontSee('context-must-not-render')->assertDontSee('secret_context');
        $this->actingAs($admin)->get('/admin/compliance/reports')->assertOk()->assertSee('recalls')->assertSee('FAILED')->assertDontSee('filter-must-not-render')->assertDontSee('failure-body-must-not-render');
    }

    public function test_ai_and_blockchain_pages_show_health_without_private_payloads(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $batch = FishBatch::query()->where('batch_code', 'FT-DEMO-0001')->firstOrFail();
        $prediction = app(SpoilagePredictionService::class)->predict($batch, $admin);
        AIServiceFailure::create(['fish_batch_id' => $batch->id, 'driver' => 'http', 'error_message' => 'api-key-must-not-render', 'attempts' => 3]);
        $event = TraceabilityEvent::query()->where('fish_batch_id', $batch->id)->firstOrFail();
        $transaction = app(TraceabilityAnchorService::class)->anchor($event);
        BlockchainVerification::create(['blockchain_transaction_id' => $transaction->id, 'is_valid' => true, 'response' => ['private_rpc_payload' => 'must-not-render'], 'verified_at' => now()]);

        $this->actingAs($admin)->get('/admin/compliance/ai')->assertOk()->assertSee($prediction->risk_level)->assertSee($prediction->model_version)->assertSee('http')->assertDontSee('api-key-must-not-render')->assertDontSee('ai_prediction_inputs');
        $this->actingAs($admin)->get('/admin/compliance/blockchain')->assertOk()->assertSee($transaction->transaction_reference)->assertSee('FT-DEMO-0001');
        $this->actingAs($admin)->get('/admin/compliance/blockchain/'.$transaction->id)->assertOk()->assertSee('VALID')->assertSee($transaction->event_hash)->assertDontSee('private_rpc_payload')->assertDontSee('must-not-render');
    }

    public function test_non_admin_cannot_access_compliance_operations(): void
    {
        Storage::fake('local');
        $this->seed();
        $user = User::query()->where('email', 'inspector@fishtrace.demo')->firstOrFail();
        $inspection = QualityInspection::query()->firstOrFail();
        $file = FileAsset::create(['organization_id' => $user->primaryOrganization()?->id, 'uploaded_by' => $user->id, 'category' => 'INSPECTION_IMAGE', 'entity_type' => 'quality_inspection', 'entity_id' => $inspection->id, 'disk' => 'local', 'path' => 'private/x.png', 'original_name' => 'x.png', 'mime_type' => 'image/png', 'extension' => 'png', 'size_bytes' => 1, 'sha256' => str_repeat('a', 64)]);
        $batch = FishBatch::query()->firstOrFail();
        $transaction = app(TraceabilityAnchorService::class)->anchor(TraceabilityEvent::query()->where('fish_batch_id', $batch->id)->firstOrFail());
        $urls = ['/admin/compliance/incidents', '/admin/compliance/incidents/'.$inspection->id, '/admin/compliance/incidents/export', '/admin/compliance/recalls', '/admin/compliance/recalls/export', '/admin/compliance/evidence', '/admin/compliance/evidence/'.$file->id.'/download', '/admin/compliance/notifications', '/admin/compliance/reports', '/admin/compliance/ai', '/admin/compliance/blockchain', '/admin/compliance/blockchain/'.$transaction->id];
        foreach ($urls as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }
}
