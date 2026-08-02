<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\FishingGearType;
use App\Models\FishSpecies;
use App\Models\LandingSite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFishingReferenceDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_manage_all_fishing_reference_registries(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $this->actingAs($admin);

        $this->post('/admin/reference-data/species', ['common_name' => 'Bigeye Tuna', 'scientific_name' => 'Thunnus obesus', 'is_active' => '1'])
            ->assertRedirect('/admin/reference-data/species');
        $species = FishSpecies::query()->where('common_name', 'Bigeye Tuna')->firstOrFail();
        $this->put('/admin/reference-data/species/'.$species->id, ['common_name' => 'Bigeye Tuna', 'scientific_name' => 'Thunnus obesus', 'is_active' => '0'])
            ->assertRedirect('/admin/reference-data/species');

        $this->post('/admin/reference-data/gear', ['name' => 'Handline', 'is_active' => '1'])
            ->assertRedirect('/admin/reference-data/gear');
        $gear = FishingGearType::query()->where('name', 'Handline')->firstOrFail();
        $this->put('/admin/reference-data/gear/'.$gear->id, ['name' => 'Hand line', 'is_active' => '0'])
            ->assertRedirect('/admin/reference-data/gear');

        $this->post('/admin/reference-data/landing-sites', ['name' => 'Galle Fisheries Harbour', 'district' => 'Galle', 'is_active' => '1'])
            ->assertRedirect('/admin/reference-data/landing-sites');
        $site = LandingSite::query()->where('name', 'Galle Fisheries Harbour')->firstOrFail();
        $this->put('/admin/reference-data/landing-sites/'.$site->id, ['name' => 'Galle Fisheries Harbour', 'district' => 'Galle', 'is_active' => '0'])
            ->assertRedirect('/admin/reference-data/landing-sites');

        $this->assertFalse($species->fresh()->is_active);
        $this->assertSame('Hand line', $gear->fresh()->name);
        $this->assertFalse($gear->fresh()->is_active);
        $this->assertFalse($site->fresh()->is_active);
        foreach (['FISH_SPECIES_CREATED', 'FISH_SPECIES_UPDATED', 'FISHING_GEAR_CREATED', 'FISHING_GEAR_UPDATED', 'LANDING_SITE_CREATED', 'LANDING_SITE_UPDATED'] as $event) {
            $this->assertTrue(AuditLog::query()->where('action', $event)->where('user_id', $admin->id)->exists(), $event.' was not audited.');
        }
    }

    public function test_reference_pages_filter_render_and_preserve_historical_usage(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $species = FishSpecies::query()->firstOrFail();
        $gear = FishingGearType::query()->firstOrFail();
        $site = LandingSite::query()->firstOrFail();
        $this->actingAs($admin);

        $this->get('/admin/reference-data/species?q=Yellowfin&sort=scientific_name&direction=asc')->assertOk()->assertSee('Yellowfin Tuna')->assertSee('1 catches')->assertSee('batches')->assertSee('Add fish species');
        $this->get('/admin/reference-data/gear?q=Longline')->assertOk()->assertSee('Longline')->assertSee('1 catches')->assertSee('Fishing gear');
        $this->get('/admin/reference-data/landing-sites?q=Matara')->assertOk()->assertSee('Mirissa Fisheries Harbour')->assertSee('1 trips')->assertSee('Landing sites');
        $this->get('/admin/reference-data/species/'.$species->id.'/edit')->assertOk()->assertSee('Inactive records remain attached');
        $this->get('/admin/reference-data/gear/'.$gear->id.'/edit')->assertOk()->assertSee('Gear name');
        $this->get('/admin/reference-data/landing-sites/'.$site->id.'/edit')->assertOk()->assertSee('District');
    }

    public function test_reference_data_validation_and_authorization_are_enforced(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $this->actingAs($admin)
            ->post('/admin/reference-data/gear', ['name' => 'Longline', 'is_active' => '1'])
            ->assertSessionHasErrors('name');
        $this->post('/admin/reference-data/species', ['common_name' => '', 'is_active' => '1'])->assertSessionHasErrors('common_name');
        $this->post('/admin/reference-data/landing-sites', ['name' => str_repeat('x', 161), 'is_active' => '1'])->assertSessionHasErrors('name');

        $fisher = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $this->actingAs($fisher)->get('/admin/reference-data/species')->assertForbidden();
        $this->post('/admin/reference-data/species', ['common_name' => 'Forbidden', 'is_active' => '1'])->assertForbidden();
        $this->get('/admin/reference-data/gear')->assertForbidden();
        $this->get('/admin/reference-data/landing-sites')->assertForbidden();
        $this->assertDatabaseMissing('fish_species', ['common_name' => 'Forbidden']);
    }

    public function test_inactive_reference_records_cannot_be_used_for_new_fishing_work(): void
    {
        $this->seed();
        $species = FishSpecies::query()->firstOrFail();
        $gear = FishingGearType::query()->firstOrFail();
        $site = LandingSite::query()->firstOrFail();
        $species->update(['is_active' => false]);
        $gear->update(['is_active' => false]);
        $site->update(['is_active' => false]);
        $fisher = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $boat = Boat::query()->where('organization_id', $fisher->primaryOrganization()?->id)->firstOrFail();
        $catch = CatchRecord::query()->where('organization_id', $fisher->primaryOrganization()?->id)->firstOrFail();
        $this->actingAs($fisher);

        $this->postJson('/api/v1/fishing-trips', ['boat_id' => $boat->id, 'landing_site_id' => $site->id])->assertStatus(422);
        $this->postJson('/api/v1/catches', ['fishing_trip_id' => $catch->fishing_trip_id, 'fish_species_id' => $species->id, 'fishing_gear_type_id' => $gear->id, 'weight_kg' => 5, 'quantity' => 1, 'caught_at' => now()->toIso8601String()])->assertStatus(422);
        $this->postJson('/api/v1/batches', ['fish_species_id' => $species->id, 'product_type' => 'Whole fish', 'catches' => [['catch_id' => $catch->id, 'weight_kg' => 1]]])->assertStatus(422);
    }
}
