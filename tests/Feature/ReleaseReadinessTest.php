<?php

namespace Tests\Feature;

use App\Services\Operations\ReleaseReadiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ReleaseReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:'.base64_encode(str_repeat('r', 32))]);
    }

    public function test_non_strict_check_passes_with_local_release_advisories(): void
    {
        $this->artisan('fishtrace:release-check')
            ->expectsOutputToContain('warnings')
            ->assertSuccessful();
    }

    public function test_strict_check_rejects_non_release_configuration(): void
    {
        $this->artisan('fishtrace:release-check', ['--strict' => true])
            ->expectsOutputToContain('failed')
            ->assertFailed();
    }

    public function test_json_output_never_exposes_configured_secrets(): void
    {
        config([
            'database.connections.sqlite.password' => 'database-secret-value',
            'fishtrace.ai.token' => 'ai-secret-value',
            'fishtrace.blockchain.token' => 'blockchain-secret-value',
        ]);

        $this->artisan('fishtrace:release-check', ['--json' => true])
            ->doesntExpectOutputToContain('database-secret-value')
            ->doesntExpectOutputToContain('ai-secret-value')
            ->doesntExpectOutputToContain('blockchain-secret-value')
            ->assertSuccessful();
    }

    public function test_checker_covers_critical_release_surfaces(): void
    {
        $keys = collect(app(ReleaseReadiness::class)->inspect())->pluck('key');

        $this->assertContains('database_connection', $keys);
        $this->assertContains('database_schema', $keys);
        $this->assertContains('private_storage', $keys);
        $this->assertContains('frontend_build', $keys);
        $this->assertContains('optimization_cache', $keys);
        $this->assertContains('firebase_driver', $keys);
        $this->assertContains('audit_retention', $keys);
        $this->assertContains('app_key_strength', $keys);
        $this->assertContains('secure_session_cookie', $keys);
        $this->assertContains('session_cookie_policy', $keys);
        $this->assertContains('mail_driver', $keys);
        $this->assertContains('failed_job_storage', $keys);
        $this->assertContains('queue_retry_window', $keys);
    }

    public function test_non_mock_http_integrations_require_https_and_tokens(): void
    {
        config([
            'fishtrace.ai.driver' => 'http',
            'fishtrace.ai.url' => 'http://insecure.example.test',
            'fishtrace.ai.token' => null,
            'fishtrace.blockchain.driver' => 'http',
            'fishtrace.blockchain.url' => null,
            'fishtrace.blockchain.token' => null,
            'fishtrace.ai.auto_predict' => false,
            'fishtrace.blockchain.auto_anchor' => false,
        ]);

        $checks = collect(app(ReleaseReadiness::class)->inspect())->keyBy('key');

        $this->assertSame('fail', $checks['ai_configuration']['status']);
        $this->assertSame('fail', $checks['blockchain_configuration']['status']);
        $this->assertSame('fail', $checks['ai_automation']['status']);
        $this->assertSame('fail', $checks['blockchain_automation']['status']);
    }

    public function test_controllers_delegate_eloquent_query_construction(): void
    {
        $violations = [];

        foreach (File::allFiles(app_path('Http/Controllers')) as $file) {
            $contents = File::get($file->getPathname());
            if (preg_match('/\b[A-Z][A-Za-z0-9_\\\\]*::(?:query|where|find|findOrFail|with|orderBy|pluck|get)\s*\(/', $contents) === 1) {
                $violations[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $violations, 'Controllers must delegate Eloquent query construction to typed services.');
    }

    public function test_domain_policies_are_discoverable(): void
    {
        $violations = [];

        foreach (File::allFiles(app_path('Policies')) as $file) {
            $policyClass = 'App\\Policies\\'.$file->getBasename('.php');
            $modelClass = 'App\\Models\\'.str_replace('Policy', '', $file->getBasename('.php'));
            $policy = class_exists($modelClass) ? Gate::getPolicyFor($modelClass) : null;

            if (! $policy instanceof $policyClass) {
                $violations[] = $modelClass;
            }
        }

        $this->assertSame([], $violations, 'Every domain policy must be discoverable for its matching model.');
    }

    public function test_controllers_delegate_streamed_response_generation(): void
    {
        $violations = [];

        foreach (File::allFiles(app_path('Http/Controllers')) as $file) {
            $contents = File::get($file->getPathname());
            if (preg_match("/(?:streamDownload|fopen\\('php:\\/\\/output'|SvgWriter|Storage::disk)/", $contents) === 1) {
                $violations[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $violations, 'Controllers must delegate streamed, QR, and private-file response generation to services.');
    }

    public function test_controller_actions_declare_return_types(): void
    {
        $violations = [];

        foreach (File::allFiles(app_path('Http/Controllers')) as $file) {
            $contents = File::get($file->getPathname());
            if (preg_match('/public\s+function\s+(?!__construct\b)\w+\s*\([^)]*\)\s*\{/s', $contents) === 1) {
                $violations[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $violations, 'Every controller action must declare an explicit return type.');
    }

    public function test_controllers_delegate_framework_facades(): void
    {
        $violations = [];

        foreach (File::allFiles(app_path('Http/Controllers')) as $file) {
            if (str_contains(File::get($file->getPathname()), 'Illuminate\\Support\\Facades')) {
                $violations[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $violations, 'Controllers must delegate framework facade integrations to Actions or Services.');
    }

    public function test_controllers_do_not_mutate_models_or_use_global_helpers(): void
    {
        $violations = [];

        foreach (File::allFiles(app_path('Http/Controllers')) as $file) {
            $contents = File::get($file->getPathname());
            if (preg_match('/(?:->(?:forceFill|save|saveQuietly|increment|decrement|createToken)\s*\(|\b(?:config|request|now|url)\s*\()/', $contents) === 1) {
                $violations[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $violations, 'Controllers must delegate persistence mutations and global-helper integrations.');
    }
}
