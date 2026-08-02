<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AdminUxTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_admin_shell_has_mobile_navigation_skip_link_and_landmarks(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();

        $this->actingAs($admin)->withSession(['success' => 'Operation completed.'])->get('/admin')->assertOk()
            ->assertSee('Skip to main content')->assertSee('href="#main-content"', false)->assertSee('id="main-content"', false)
            ->assertSee('aria-label="Primary navigation"', false)->assertSee('<summary', false)->assertSee('Navigation')
            ->assertSee('aria-current="page"', false)->assertSee('role="status"', false)->assertSee('Operation completed.');
    }

    public function test_primary_and_section_navigation_expose_the_active_route(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();

        $retail = $this->actingAs($admin)->get('/admin/retail/inventory?status=IN_STOCK')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/aria-current="page"\s*>Retail operations/', $retail);
        $this->assertMatchesRegularExpression('/aria-current="page"\s*>Inventory/', $retail);
        $this->assertStringContainsString('value="IN_STOCK" selected', $retail);

        $telemetry = $this->actingAs($admin)->get('/admin/transport/telemetry')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/aria-current="page"\s*>Sensor history/', $telemetry);
        $this->assertMatchesRegularExpression('/aria-current="page"\s*>Telemetry/', $telemetry);
        $this->assertDoesNotMatchRegularExpression('/aria-current="page"\s*>Transport &amp; IoT/', $telemetry);
    }

    public function test_status_component_uses_semantic_tones(): void
    {
        $failed = Blade::render('<x-admin.status value="FAILED" />');
        $active = Blade::render('<x-admin.status value="ACTIVE" />');
        $warning = Blade::render('<x-admin.status value="QUALITY_HOLD" />');

        $this->assertStringContainsString('FAILED', $failed);
        $this->assertStringContainsString('bg-red-100', $failed);
        $this->assertStringContainsString('ACTIVE', $active);
        $this->assertStringContainsString('bg-emerald-100', $active);
        $this->assertStringContainsString('QUALITY HOLD', $warning);
        $this->assertStringContainsString('bg-amber-100', $warning);
    }

    public function test_frontend_enhancement_layer_covers_tables_filters_and_accessible_controls(): void
    {
        $javascript = file_get_contents(resource_path('js/app.js'));
        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertIsString($javascript);
        $this->assertIsString($css);
        $this->assertStringContainsString("querySelectorAll('table')", $javascript);
        $this->assertStringContainsString("setAttribute('aria-label'", $javascript);
        $this->assertStringContainsString('Clear filters', $javascript);
        $this->assertStringContainsString('URLSearchParams', $javascript);
        $this->assertStringContainsString('.admin-main table', $css);
        $this->assertStringContainsString('prefers-reduced-motion', $css);
        $this->assertStringContainsString(':focus-visible', $css);
    }

    public function test_representative_admin_modules_render_inside_the_hardened_shell(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        foreach (['/admin/users', '/admin/batches', '/admin/fishing/trips', '/admin/transport/devices', '/admin/processor/records', '/admin/retail/inventory', '/admin/compliance/incidents', '/admin/audit-logs'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk()->assertSee('id="main-content"', false)->assertSee('Skip to main content')->assertSee('aria-label="Primary navigation"', false);
        }
    }
}
