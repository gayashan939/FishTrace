<?php

namespace Tests\Feature;

use App\Contracts\Firebase\FirebaseRealtimeClient;
use App\Enums\ReportExportStatus;
use App\Jobs\GenerateReportExport;
use App\Models\Boat;
use App\Models\FileAsset;
use App\Models\IotDevice;
use App\Models\ReportExport;
use App\Models\TransportTrip;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SecurityHardeningContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_failures_are_allowlisted_deduplicated_and_resolved_on_retry(): void
    {
        $this->seed();
        $device = IotDevice::query()->firstOrFail();
        $trip = TransportTrip::query()->where('status', 'ACTIVE')->firstOrFail();
        $firebase = app(FirebaseRealtimeClient::class);
        $path = 'telemetry/'.$device->firebase_uid.'/security-failure-message';
        $invalid = [
            'tripId' => $trip->id,
            'productTemperature' => 3.4,
            'recordedAt' => now()->getTimestampMs(),
            'schemaVersion' => 999,
            'providerSecret' => 'must-never-be-persisted',
        ];
        $firebase->set($path, $invalid);

        $this->artisan('fishtrace:sync-firebase-telemetry')->assertFailed();
        $this->artisan('fishtrace:sync-firebase-telemetry', ['--retry-failed' => true])->assertFailed();

        $failure = \DB::table('firebase_sync_failures')->where('message_id', 'security-failure-message')->first();
        $this->assertNotNull($failure);
        $this->assertSame(2, (int) $failure->retry_count);
        $this->assertStringNotContainsString('must-never-be-persisted', (string) $failure->payload);
        $this->assertStringNotContainsString('must-never-be-persisted', (string) $failure->error_message);
        $this->assertSame('IMPORT_FAILED', $firebase->get($path)['syncError']);
        $this->assertDatabaseCount('firebase_sync_failures', 1);

        $firebase->set($path, array_merge($invalid, ['schemaVersion' => 1, 'syncStatus' => 'FAILED']));
        $this->artisan('fishtrace:sync-firebase-telemetry', ['--retry-failed' => true])->assertSuccessful();
        $this->assertDatabaseHas('firebase_sync_failures', ['message_id' => 'security-failure-message', 'retry_count' => 2]);
        $this->assertNotNull(\DB::table('firebase_sync_failures')->where('message_id', 'security-failure-message')->value('resolved_at'));
        $this->assertSame('SYNCED', $firebase->get($path)['syncStatus']);
    }

    public function test_report_terminal_failure_does_not_persist_exception_details(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'retailer@fishtrace.demo')->firstOrFail();
        $export = ReportExport::query()->create([
            'organization_id' => $user->primaryOrganization()?->id,
            'requested_by' => $user->id,
            'report_type' => 'inventory',
            'filters' => [],
            'status' => ReportExportStatus::PENDING,
        ]);

        (new GenerateReportExport($export->id))->failed(new RuntimeException('storage leaked secret-path-value'));

        $message = (string) $export->fresh()->failure_message;
        $this->assertStringNotContainsString('secret-path-value', $message);
        $this->assertSame('Report export failed (RuntimeException).', $message);
    }

    public function test_failed_physical_file_deletion_rolls_back_database_deletion(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($user);
        $file = FileAsset::query()->create([
            'organization_id' => $user->primaryOrganization()?->id,
            'uploaded_by' => $user->id,
            'category' => 'BOAT_IMAGE',
            'entity_type' => 'boat',
            'entity_id' => Boat::query()->firstOrFail()->id,
            'disk' => 'local',
            'path' => 'organizations/test/undeletable.png',
            'original_name' => 'undeletable.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size_bytes' => 10,
            'sha256' => hash('sha256', 'test'),
        ]);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with($file->path)->andReturnTrue();
        $disk->shouldReceive('delete')->once()->with($file->path)->andReturnFalse();
        Storage::shouldReceive('disk')->once()->with('local')->andReturn($disk);

        $this->deleteJson("/api/v1/files/{$file->id}")->assertServerError();

        $this->assertDatabaseHas('file_assets', ['id' => $file->id]);
    }

    public function test_authenticated_mutations_are_rate_limited_with_api_error_code(): void
    {
        $this->seed();
        Sanctum::actingAs(User::query()->where('email', 'retailer@fishtrace.demo')->firstOrFail());
        RateLimiter::for('authenticated-api', fn () => Limit::perMinute(2)->by('security-test'));

        $this->postJson('/api/v1/notifications/read-all')->assertOk();
        $this->postJson('/api/v1/notifications/read-all')->assertOk();
        $this->postJson('/api/v1/notifications/read-all')
            ->assertTooManyRequests()
            ->assertJsonPath('error.code', 'TOO_MANY_REQUESTS');
    }
}
