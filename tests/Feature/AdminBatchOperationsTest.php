<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\FishBatch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBatchOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_and_filter_the_batch_registry(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();

        $this->actingAs($admin)->get('/admin/batches')->assertOk()->assertSee('Batch registry')->assertSee('FT-DEMO-0001')->assertSee('Export CSV');
        $this->actingAs($admin)->get('/admin/batches?type=RAW&status=IN_TRANSPORT')->assertOk()->assertSee('FT-DEMO-0001')->assertDontSee('FT-DEMO-0001-01');
        $this->actingAs($admin)->get('/admin/batches?q=does-not-exist')->assertOk()->assertSee('No batches match these filters.')->assertDontSee('FT-DEMO-0001');
    }

    public function test_admin_can_follow_origin_splits_transport_and_telemetry_on_batch_details(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $batch = FishBatch::query()->where('batch_code', 'FT-DEMO-0001')->firstOrFail();

        $this->actingAs($admin)->get('/admin/batches/'.$batch->id)
            ->assertOk()
            ->assertSee('Catch origin')
            ->assertSee('Sagara Kumari')
            ->assertSee('FT-DEMO-0001-01')
            ->assertSee('Traceability timeline')
            ->assertSee('TTR-DEMO-001')
            ->assertSee('Reefer Sensor 01')
            ->assertSee('12')
            ->assertSee('Batch audit history')
            ->assertDontSee('demo-trace-yellowfin-tuna-2026')
            ->assertDontSee('raw_payload');
        $this->actingAs($admin)->get('/admin/batches/'.$batch->id.'/qr')->assertOk()->assertHeader('content-type', 'image/svg+xml')->assertSee('<svg', false);
    }

    public function test_child_batch_details_link_back_to_parent_and_retail_position(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $child = FishBatch::query()->where('batch_code', 'FT-DEMO-0001-01')->firstOrFail();

        $this->actingAs($admin)->get('/admin/batches/'.$child->id)->assertOk()->assertSee('Parent:')->assertSee('FT-DEMO-0001')->assertSee('Ocean Fresh Colombo')->assertSee('IN STOCK');
    }

    public function test_filtered_csv_export_is_bounded_and_audited(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $fisherOrganization = Organization::query()->where('type', 'FISHER')->firstOrFail();

        $response = $this->actingAs($admin)->get('/admin/batches/export?organization_id='.$fisherOrganization->id.'&type=RAW');

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('FT-DEMO-0001', $csv);
        $this->assertStringNotContainsString('FT-DEMO-0001-01', $csv);
        $this->assertTrue(AuditLog::query()->where('action', 'BATCH_DIRECTORY_EXPORTED')->where('user_id', $admin->id)->exists());
    }

    public function test_non_admin_cannot_access_batch_operations_or_exports(): void
    {
        $this->seed();
        $fisher = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $batch = FishBatch::query()->where('batch_code', 'FT-DEMO-0001')->firstOrFail();

        $this->actingAs($fisher)->get('/admin/batches')->assertForbidden();
        $this->actingAs($fisher)->get('/admin/batches/'.$batch->id)->assertForbidden();
        $this->actingAs($fisher)->get('/admin/batches/'.$batch->id.'/qr')->assertForbidden();
        $this->actingAs($fisher)->get('/admin/batches/export')->assertForbidden();
    }
}
