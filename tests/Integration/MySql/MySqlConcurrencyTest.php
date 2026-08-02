<?php

namespace Tests\Integration\MySql;

use App\Models\InventoryLot;
use App\Models\RetailLocation;
use App\Models\RetailSale;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use Throwable;

class MySqlConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('mysql', config('database.default'));
        $this->assertSame('fishtrace_test', config('database.connections.mysql.database'), 'The destructive MySQL integration suite may only use fishtrace_test.');

        try {
            $this->app['db']->connection()->getPdo();
        } catch (Throwable) {
            $this->fail('MySQL acceptance tests require a running server and a dedicated fishtrace_test database/user. See docs/testing.md.');
        }

        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    public function test_mysql_schema_and_seed_are_compatible(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', ['email' => 'admin@fishtrace.demo']);
        $this->assertDatabaseHas('inventory_lots', ['status' => 'IN_STOCK']);
    }

    public function test_concurrent_sales_cannot_oversell_the_last_package(): void
    {
        $this->seed();
        $retailer = User::where('email', 'retailer@fishtrace.demo')->firstOrFail();
        $location = RetailLocation::where('organization_id', $retailer->primaryOrganization()?->id)->firstOrFail();
        $lot = InventoryLot::where('organization_id', $retailer->primaryOrganization()?->id)->firstOrFail();
        $lot->update(['available_packages' => 1, 'reserved_packages' => 0, 'sold_packages' => 0, 'status' => 'IN_STOCK']);

        $environment = $this->childEnvironment();
        $script = base_path('tests/Support/attempt_retail_sale.php');
        $processes = collect([Str::uuid()->toString(), Str::uuid()->toString()])->map(
            fn (string $reference): Process => new Process([PHP_BINARY, $script, $retailer->id, $location->id, $lot->id, $reference], base_path(), $environment),
        );

        $processes->each(fn (Process $process) => $process->start());
        $processes->each(fn (Process $process) => $process->wait());

        $outcomes = $processes->map(fn (Process $process): string => trim($process->getOutput()))->sort()->values()->all();
        $this->assertSame(['REJECTED', 'SOLD'], $outcomes, $processes->map(fn (Process $process): string => $process->getErrorOutput())->implode(PHP_EOL));

        $lot->refresh();
        $this->assertSame(0, $lot->available_packages);
        $this->assertSame(1, $lot->sold_packages);
        $this->assertSame('SOLD_OUT', $lot->getRawOriginal('status'));
        $this->assertSame(1, RetailSale::count());
    }

    /** @return array<string, string> */
    private function childEnvironment(): array
    {
        $connection = config('database.connections.mysql');

        return [
            'APP_ENV' => 'testing',
            'APP_KEY' => (string) config('app.key'),
            'APP_CONFIG_CACHE' => 'bootstrap/cache/config-mysql-testing.php',
            'CACHE_STORE' => 'array',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => (string) $connection['host'],
            'DB_PORT' => (string) $connection['port'],
            'DB_DATABASE' => 'fishtrace_test',
            'DB_USERNAME' => (string) $connection['username'],
            'DB_PASSWORD' => (string) $connection['password'],
            'DB_URL' => '',
            'FIREBASE_DRIVER' => 'mock',
            'AI_DRIVER' => 'mock',
            'BLOCKCHAIN_DRIVER' => 'mock',
            'QUEUE_CONNECTION' => 'sync',
            'SESSION_DRIVER' => 'array',
        ];
    }
}
