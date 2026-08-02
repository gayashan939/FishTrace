<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Boat;
use App\Models\FishBatch;
use App\Models\FishSpecies;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use LogicException;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_and_model_mutations_are_audited_with_request_context_and_redaction(): void
    {
        Storage::fake('local');
        $this->seed();
        $requestId = (string) Str::uuid();
        $login = $this->withHeader('X-Request-ID', $requestId)->postJson('/api/v1/auth/login', ['email' => 'fisher@fishtrace.demo', 'password' => 'FishTrace@2026', 'device_name' => 'Audit Test'])->assertOk();
        $token = $login->json('data.token');
        $fisher = User::where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['action' => 'AUTH_LOGIN_SUCCEEDED', 'user_id' => $fisher->id, 'organization_id' => $fisher->primaryOrganization()?->id, 'request_id' => $requestId]);

        $boat = Boat::where('organization_id', $fisher->primaryOrganization()?->id)->firstOrFail();
        $uploadRequestId = (string) Str::uuid();
        $contents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true).str_repeat("\0", 1024);
        $response = $this->withToken($token)->withHeader('X-Request-ID', $uploadRequestId)->post('/api/v1/files', ['category' => 'BOAT_IMAGE', 'entity_type' => 'boat', 'entity_id' => $boat->id, 'file' => UploadedFile::fake()->createWithContent('boat.png', $contents)], ['Accept' => 'application/json'])->assertCreated();
        $fileId = $response->json('data.id');
        $log = AuditLog::where('action', 'FILE_ASSET_CREATED')->where('auditable_id', $fileId)->firstOrFail();
        $this->assertSame($uploadRequestId, $log->request_id);
        $this->assertSame('[REDACTED]', $log->new_values['path']);
        $this->assertSame('[REDACTED]', $log->new_values['disk']);
        $this->assertArrayNotHasKey('password', $log->new_values);
    }

    public function test_admin_has_documented_regulator_visibility_while_other_roles_are_scoped_or_denied(): void
    {
        $this->seed();
        $processor = User::where('email', 'processor@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($processor);
        $batch = FishBatch::create(['organization_id' => User::where('email', 'fisher@fishtrace.demo')->firstOrFail()->primaryOrganization()?->id, 'created_by' => User::where('email', 'fisher@fishtrace.demo')->firstOrFail()->id, 'fish_species_id' => FishSpecies::firstOrFail()->id, 'batch_code' => 'AUDIT-BATCH-001', 'type' => 'RAW', 'status' => 'AVAILABLE_FOR_PROCESSING', 'product_type' => 'Whole fish', 'total_weight_kg' => 10]);
        $this->postJson("/api/v1/processor/batches/{$batch->id}/accept", ['received_weight_kg' => 10])->assertCreated();
        $processorOrg = $processor->primaryOrganization();

        Sanctum::actingAs(User::where('email', 'admin@fishtrace.demo')->firstOrFail());
        $result = $this->getJson('/api/v1/audit-logs?organization_id='.$processorOrg?->id.'&action=BATCH_INTAKE_CREATED')->assertOk()->assertJsonPath('data.data.0.action', 'BATCH_INTAKE_CREATED');
        $logId = $result->json('data.data.0.id');
        $this->getJson("/api/v1/audit-logs/{$logId}")->assertOk();
        $csv = $this->get('/api/v1/audit-logs/export?organization_id='.$processorOrg?->id)->assertOk();
        $this->assertStringContainsString('BATCH_INTAKE_CREATED', $csv->streamedContent());
        $this->actingAs(User::where('email', 'admin@fishtrace.demo')->firstOrFail())->get('/admin/audit-logs?action=BATCH_INTAKE_CREATED')->assertOk()->assertSee('BATCH_INTAKE_CREATED')->assertSee('View redacted state');

        Sanctum::actingAs(User::where('email', 'inspector@fishtrace.demo')->firstOrFail());
        $this->getJson("/api/v1/audit-logs/{$logId}")->assertForbidden();
        $this->getJson('/api/v1/audit-logs?organization_id='.$processorOrg?->id)->assertForbidden();

        Sanctum::actingAs(User::where('email', 'fisher@fishtrace.demo')->firstOrFail());
        $this->getJson('/api/v1/audit-logs')->assertForbidden();
    }

    public function test_audit_records_are_immutable_and_retention_has_a_one_year_minimum(): void
    {
        $this->seed();
        $id = (string) Str::uuid();
        DB::table('audit_logs')->insert(['id' => $id, 'action' => 'LEGACY_EVENT', 'created_at' => now()->subDays(400)]);
        $log = AuditLog::findOrFail($id);
        try {
            $log->update(['action' => 'TAMPERED']);
            $this->fail('Audit update should have been rejected.');
        } catch (LogicException) {
            $this->assertDatabaseHas('audit_logs', ['id' => $id, 'action' => 'LEGACY_EVENT']);
        }
        try {
            $log->delete();
            $this->fail('Audit deletion should have been rejected.');
        } catch (LogicException) {
            $this->assertDatabaseHas('audit_logs', ['id' => $id]);
        }

        $this->artisan('fishtrace:prune-audit-logs', ['--days' => 1, '--dry-run' => true])->assertSuccessful();
        $this->assertDatabaseHas('audit_logs', ['id' => $id]);
        $this->artisan('fishtrace:prune-audit-logs', ['--days' => 1])->assertSuccessful();
        $this->assertDatabaseMissing('audit_logs', ['id' => $id]);
    }
}
