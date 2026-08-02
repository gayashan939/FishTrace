<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BatchIntake;
use App\Models\PackageLabel;
use App\Models\ProcessingRecord;
use App\Models\ProcessorProfile;
use App\Models\QualityInspection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProcessorOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_navigate_facility_intake_and_processing_records(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $profile = ProcessorProfile::query()->firstOrFail();
        $intake = BatchIntake::query()->firstOrFail();
        $record = ProcessingRecord::query()->firstOrFail();

        $this->actingAs($admin)->get('/admin/processor/facilities')->assertOk()->assertSee('Ceylon Blue Processing - Mirissa')->assertSee('SL-FP-2026-001');
        $this->actingAs($admin)->get('/admin/processor/facilities/'.$profile->id)->assertOk()->assertSee('Newest 100 intakes')->assertSee('FT-DEMO-0001');
        $this->actingAs($admin)->get('/admin/processor/intakes')->assertOk()->assertSee('Batch intake queue')->assertSee('ACCEPTED');
        $this->actingAs($admin)->get('/admin/processor/intakes/'.$intake->id)->assertOk()->assertSee('Yellowfin Tuna')->assertSee('Open Chilled Whole Fish Processing');
        $this->actingAs($admin)->get('/admin/processor/records')->assertOk()->assertSee('Chilled Whole Fish Processing')->assertSee('COMPLETED');
        $this->actingAs($admin)->get('/admin/processor/records/'.$record->id)->assertOk()->assertSee('Ordered processing steps')->assertSee('CLEANING')->assertSee('PACKAGING')->assertSee('FT-DEMO-0001-01');
    }

    public function test_admin_can_inspect_quality_and_package_custody_without_public_tokens(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $inspection = QualityInspection::query()->firstOrFail();
        $receivedLabel = PackageLabel::query()->where('label_code', 'LBL-DEMO-RETAIL-01')->firstOrFail();

        $this->actingAs($admin)->get('/admin/processor/inspections')->assertOk()->assertSee('GRADE-A')->assertSee('Dinuka Wijesinghe');
        $this->actingAs($admin)->get('/admin/processor/inspections/'.$inspection->id)->assertOk()->assertSee('Bright and firm')->assertSee('Fresh');
        $this->actingAs($admin)->get('/admin/processor/labels')->assertOk()->assertSee('LBL-DEMO-RETAIL-01')->assertSee('RECEIVED')->assertDontSee('demo-retail-package-01');
        $this->actingAs($admin)->get('/admin/processor/labels/'.$receivedLabel->id)->assertOk()->assertSee('Ocean Fresh Markets')->assertSee('Ocean Fresh Colombo')->assertDontSee('demo-retail-package-01')->assertDontSee('public_token');
    }

    public function test_processor_filters_are_applied_server_side(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();

        $this->actingAs($admin)->get('/admin/processor/intakes?status=REJECTED')->assertOk()->assertSee('No intakes match.')->assertDontSee('FT-DEMO-0001</a>', false);
        $this->actingAs($admin)->get('/admin/processor/records?status=QUALITY_HOLD')->assertOk()->assertSee('No processing records match.');
        $this->actingAs($admin)->get('/admin/processor/inspections?result=FAILED')->assertOk()->assertSee('No inspections match.');
        $this->actingAs($admin)->get('/admin/processor/labels?q=RETAIL-02')->assertOk()->assertSee('LBL-DEMO-RETAIL-02')->assertDontSee('LBL-DEMO-RETAIL-01');
    }

    public function test_processing_and_inspection_exports_are_bounded_filtered_and_audited(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();

        $records = $this->actingAs($admin)->get('/admin/processor/records/export?status=COMPLETED');
        $records->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('FT-DEMO-0001', $records->streamedContent());
        $this->assertStringNotContainsString('public_token', $records->streamedContent());
        $inspections = $this->actingAs($admin)->get('/admin/processor/inspections/export?result=PASSED');
        $inspections->assertOk();
        $this->assertStringContainsString('GRADE-A', $inspections->streamedContent());
        $this->assertTrue(AuditLog::query()->where('action', 'PROCESSING_RECORDS_EXPORTED')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'QUALITY_INSPECTIONS_EXPORTED')->exists());
    }

    public function test_non_admin_cannot_access_processor_operations(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'processor@fishtrace.demo')->firstOrFail();
        $profile = ProcessorProfile::query()->firstOrFail();
        $intake = BatchIntake::query()->firstOrFail();
        $record = ProcessingRecord::query()->firstOrFail();
        $inspection = QualityInspection::query()->firstOrFail();
        $label = PackageLabel::query()->firstOrFail();
        $urls = ['/admin/processor/facilities', '/admin/processor/facilities/'.$profile->id, '/admin/processor/intakes', '/admin/processor/intakes/'.$intake->id, '/admin/processor/records', '/admin/processor/records/'.$record->id, '/admin/processor/records/export', '/admin/processor/inspections', '/admin/processor/inspections/'.$inspection->id, '/admin/processor/inspections/export', '/admin/processor/labels', '/admin/processor/labels/'.$label->id];

        foreach ($urls as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }
}
