<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\FishingTrip;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminFishingOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_navigate_fisher_boat_trip_and_catch_operations(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $fisher = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $boat = Boat::query()->where('registration_number', 'IMUL-A-1042-MTR')->firstOrFail();
        $trip = FishingTrip::query()->where('trip_code', 'FTR-DEMO-001')->firstOrFail();
        $catch = CatchRecord::query()->where('fishing_trip_id', $trip->id)->firstOrFail();

        $this->actingAs($admin)->get('/admin/fishing/fishers')->assertOk()->assertSee('Nimal Fernando')->assertSee('Operational profiles');
        $this->actingAs($admin)->get('/admin/fishing/fishers/'.$fisher->id)->assertOk()->assertSee('Sagara Kumari')->assertSee('Recorded catch');
        $this->actingAs($admin)->get('/admin/fishing/boats')->assertOk()->assertSee('IMUL-A-1042-MTR');
        $this->actingAs($admin)->get('/admin/fishing/boats/'.$boat->id)->assertOk()->assertSee('Trip history')->assertSee('FTR-DEMO-001');
        $this->actingAs($admin)->get('/admin/fishing/trips')->assertOk()->assertSee('FTR-DEMO-001')->assertSee('Mirissa Fisheries Harbour');
        $this->actingAs($admin)->get('/admin/fishing/trips/'.$trip->id)->assertOk()->assertSee('Catch allocation reconciliation')->assertSee('20.000 kg remains available');
        $this->actingAs($admin)->get('/admin/fishing/catches')->assertOk()->assertSee('Reconcile landed weight')->assertSee('20.000 kg');
        $this->actingAs($admin)->get('/admin/fishing/catches/'.$catch->id)->assertOk()->assertSee('Batch allocations')->assertSee('FT-DEMO-0001');
    }

    public function test_catch_allocation_filters_use_the_pivot_ledger(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();

        $this->actingAs($admin)->get('/admin/fishing/catches?allocation=PARTIAL')->assertOk()->assertSee('FTR-DEMO-001');
        $this->actingAs($admin)->get('/admin/fishing/catches?allocation=FULL')->assertOk()->assertSee('No catches match these filters.')->assertDontSee('FTR-DEMO-001');
    }

    public function test_overallocation_and_stored_ledger_mismatch_are_visible(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $catch = CatchRecord::query()->firstOrFail();
        DB::table('batch_catches')->where('catch_record_id', $catch->id)->update(['allocated_weight_kg' => 130]);

        $this->actingAs($admin)->get('/admin/fishing/catches/'.$catch->id)->assertOk()->assertSee('Overallocated by 10.000 kg');
        $this->actingAs($admin)->get('/admin/fishing/trips/'.$catch->fishing_trip_id)->assertOk()->assertSee('Allocation integrity warning')->assertSee('OVERALLOCATED · STORED MISMATCH');
        $this->actingAs($admin)->get('/admin/fishing/catches?allocation=MISMATCH')->assertOk()->assertSee('FTR-DEMO-001');
    }

    public function test_trip_and_catch_exports_apply_filters_and_are_audited(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $organization = Organization::query()->where('type', 'FISHER')->firstOrFail();

        $trips = $this->actingAs($admin)->get('/admin/fishing/trips/export?organization_id='.$organization->id.'&status=COMPLETED');
        $trips->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('FTR-DEMO-001', $trips->streamedContent());

        $catches = $this->actingAs($admin)->get('/admin/fishing/catches/export?organization_id='.$organization->id.'&allocation=PARTIAL');
        $catches->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Yellowfin Tuna', $catches->streamedContent());
        $this->assertTrue(AuditLog::query()->where('action', 'FISHING_TRIPS_EXPORTED')->where('user_id', $admin->id)->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'CATCH_RECORDS_EXPORTED')->where('user_id', $admin->id)->exists());
    }

    public function test_non_admin_cannot_access_fishing_operations(): void
    {
        $this->seed();
        $fisher = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $trip = FishingTrip::query()->firstOrFail();
        $catch = CatchRecord::query()->firstOrFail();

        $this->actingAs($fisher)->get('/admin/fishing/fishers')->assertForbidden();
        $this->actingAs($fisher)->get('/admin/fishing/boats')->assertForbidden();
        $this->actingAs($fisher)->get('/admin/fishing/trips')->assertForbidden();
        $this->actingAs($fisher)->get('/admin/fishing/trips/'.$trip->id)->assertForbidden();
        $this->actingAs($fisher)->get('/admin/fishing/catches')->assertForbidden();
        $this->actingAs($fisher)->get('/admin/fishing/catches/'.$catch->id)->assertForbidden();
        $this->actingAs($fisher)->get('/admin/fishing/trips/export')->assertForbidden();
        $this->actingAs($fisher)->get('/admin/fishing/catches/export')->assertForbidden();
    }
}
