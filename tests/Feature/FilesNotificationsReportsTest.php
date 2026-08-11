<?php

namespace Tests\Feature;

use App\Enums\ReportType;
use App\Models\Boat;
use App\Models\FileAsset;
use App\Models\InventoryLot;
use App\Models\Organization;
use App\Models\ReportExport;
use App\Models\TransportTrip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FilesNotificationsReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_directories_are_bounded_and_internal_notification_report_fields_are_hidden(): void
    {
        $this->seed();
        $retailer = User::where('email', 'retailer@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($retailer);
        $batchId = (string) InventoryLot::query()->where('organization_id', $retailer->primaryOrganization()?->id)->value('fish_batch_id');
        $retailer->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'InternalContractNotification',
            'data' => [
                'type' => 'RECALL',
                'title' => 'Contract privacy check',
                'message' => 'Review the affected inventory.',
                'context' => ['batch_id' => $batchId, 'reason' => 'private-reason', 'secret_context' => 'private-context-value'],
            ],
        ]);
        $export = ReportExport::query()->create([
            'organization_id' => $retailer->primaryOrganization()?->id,
            'requested_by' => $retailer->id,
            'report_type' => 'inventory',
            'filters' => [],
            'status' => 'FAILED',
            'failure_message' => 'private-report-failure-detail',
        ]);

        $this->getJson('/api/v1/notifications?per_page=101')->assertUnprocessable();
        $this->getJson('/api/v1/reports/exports?per_page=101')->assertUnprocessable();
        $this->getJson('/api/v1/notifications?type=RECALL')
            ->assertOk()
            ->assertJsonPath('data.data.0.data.context.batch_id', $batchId)
            ->assertJsonMissingPath('data.data.0.data.context.reason')
            ->assertDontSee('private-context-value');
        $this->getJson('/api/v1/reports/exports')
            ->assertOk()
            ->assertDontSee('failure_message')
            ->assertDontSee('private-report-failure-detail');
        $this->getJson("/api/v1/reports/exports/{$export->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.export.failure_message')
            ->assertDontSee('private-report-failure-detail');
    }

    public function test_private_file_upload_download_authorization_and_delete(): void
    {
        Storage::fake('local');
        $this->seed();
        $fisher = User::where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $boat = Boat::where('organization_id', $fisher->primaryOrganization()?->id)->firstOrFail();
        Sanctum::actingAs($fisher);

        $response = $this->post('/api/v1/files', ['category' => 'BOAT_IMAGE', 'entity_type' => 'boat', 'entity_id' => $boat->id, 'file' => $this->png('vessel.png')], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'category', 'entity_type', 'entity_id', 'original_name', 'mime_type', 'extension', 'size_bytes', 'sha256', 'download_url', 'created_at']])
            ->assertJsonPath('data.category', 'BOAT_IMAGE')
            ->assertJsonPath('data.entity_type', 'boat')
            ->assertJsonPath('data.entity_id', $boat->id)
            ->assertJsonPath('data.original_name', 'vessel.png')
            ->assertJsonPath('data.mime_type', 'image/png')
            ->assertJsonPath('data.extension', 'png')
            ->assertJsonMissingPath('data.path')
            ->assertJsonMissingPath('data.disk');
        $fileId = $response->json('data.id');
        $asset = FileAsset::findOrFail($fileId);
        Storage::disk('local')->assertExists($asset->path);
        $this->get("/api/v1/files/{$fileId}")->assertOk()->assertHeader('content-type', 'image/png');

        Sanctum::actingAs(User::where('email', 'retailer@fishtrace.demo')->firstOrFail());
        $this->get("/api/v1/files/{$fileId}")->assertForbidden();
        $this->deleteJson("/api/v1/files/{$fileId}")->assertForbidden();

        Sanctum::actingAs($fisher);
        $this->deleteJson("/api/v1/files/{$fileId}")->assertNoContent();
        Storage::disk('local')->assertMissing($asset->path);
        $this->assertDatabaseMissing('file_assets', ['id' => $fileId]);
    }

    public function test_file_category_mime_and_resource_pairing_are_validated(): void
    {
        Storage::fake('local');
        $this->seed();
        $fisher = User::where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $boat = Boat::firstOrFail();
        Sanctum::actingAs($fisher);

        $this->post('/api/v1/files', ['category' => 'BATCH_DOCUMENT', 'entity_type' => 'boat', 'entity_id' => $boat->id, 'file' => UploadedFile::fake()->create('script.php', 10, 'application/x-php')], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->post('/api/v1/files', ['category' => 'BOAT_IMAGE', 'entity_type' => 'fish_batch', 'entity_id' => $boat->id, 'file' => $this->png('boat.png')], ['Accept' => 'application/json'])->assertUnprocessable();
    }

    public function test_notifications_support_filters_counts_and_read_state(): void
    {
        $this->seed();
        $retailer = User::where('email', 'retailer@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($retailer);

        $list = $this->getJson('/api/v1/notifications?read=unread&type=RETAIL_RECEIPT')->assertOk()->assertJsonPath('data.data.0.data.type', 'RETAIL_RECEIPT');
        $notificationId = $list->json('data.data.0.id');
        $this->getJson('/api/v1/notifications/unread-count')->assertOk()->assertJsonPath('data.count', 1);
        $this->postJson("/api/v1/notifications/{$notificationId}/read")->assertOk()->assertJsonPath('data.id', $notificationId);
        $this->getJson('/api/v1/notifications/unread-count')->assertOk()->assertJsonPath('data.count', 0);
        $this->postJson('/api/v1/notifications/read-all')->assertOk()->assertJsonPath('data.marked_read', 0);
    }

    public function test_offline_device_and_delivery_notifications_are_deduplicated_and_scoped(): void
    {
        $this->seed();
        $transporter = User::where('email', 'transporter@fishtrace.demo')->firstOrFail();
        $this->artisan('fishtrace:check-offline-devices')->assertSuccessful();
        $this->artisan('fishtrace:check-offline-devices')->assertSuccessful();
        Sanctum::actingAs($transporter);
        $this->getJson('/api/v1/notifications?type=DEVICE_OFFLINE')->assertOk()->assertJsonCount(1, 'data.data');

        $trip = TransportTrip::where('organization_id', $transporter->primaryOrganization()?->id)->firstOrFail();
        $this->postJson("/api/v1/transport-trips/{$trip->id}/delivery-confirmation", ['receiver_name' => 'Colombo Receiving Desk', 'delivered_at' => now()->toIso8601String()])->assertOk();
        $this->postJson("/api/v1/transport-trips/{$trip->id}/complete")->assertOk()->assertJsonPath('data.status', 'COMPLETED');
        $this->assertDatabaseHas('device_assignments', ['transport_trip_id' => $trip->id, 'status' => 'ENDED', 'firebase_sync_status' => 'SYNCED']);
        $this->getJson('/api/v1/notifications?type=DELIVERY_COMPLETED')->assertOk()->assertJsonPath('data.data.0.data.type', 'DELIVERY_COMPLETED');
    }

    public function test_reports_are_scoped_and_support_json_csv_print_and_queued_exports(): void
    {
        Storage::fake('local');
        $this->seed();
        $retailer = User::where('email', 'retailer@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($retailer);

        $this->getJson('/api/v1/reports/summary')->assertOk()->assertJsonPath('data.available_inventory_packages', 4);
        $this->getJson('/api/v1/reports/inventory')->assertOk()->assertJsonPath('data.row_count', 1)->assertJsonPath('data.rows.0.status', 'IN_STOCK');
        foreach (ReportType::cases() as $type) {
            $this->getJson('/api/v1/reports/'.$type->value)->assertOk()->assertJsonPath('data.report', $type->value);
        }
        $csv = $this->get('/api/v1/reports/inventory/csv')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('label_code', $csv->streamedContent());
        $this->get('/api/v1/reports/inventory/print')->assertOk()->assertSee('Inventory')->assertSee('LBL-DEMO-RETAIL-01');

        $export = $this->postJson('/api/v1/reports/exports', ['report_type' => 'inventory'])->assertStatus(202)->assertJsonPath('data.status', 'COMPLETED')->json('data');
        $details = $this->getJson("/api/v1/reports/exports/{$export['id']}")->assertOk()->assertJsonPath('data.export.status', 'COMPLETED')->json('data');
        $this->assertNotNull($details['file']);
        $this->assertDatabaseHas('file_assets', ['entity_type' => 'report_export', 'entity_id' => $export['id'], 'category' => 'REPORT_EXPORT']);
        $this->get($details['file']['download_url'])->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->getJson('/api/v1/notifications?type=REPORT_EXPORT_READY')->assertOk()->assertJsonPath('data.data.0.data.type', 'REPORT_EXPORT_READY');

        $otherOrganization = Organization::where('type', 'PROCESSOR')->firstOrFail();
        $this->getJson('/api/v1/reports/inventory?organization_id='.$otherOrganization->id)->assertForbidden();
    }

    private function png(string $name): UploadedFile
    {
        $contents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        $this->assertIsString($contents);
        $contents .= str_repeat("\0", 1024);

        return UploadedFile::fake()->createWithContent($name, $contents);
    }
}
