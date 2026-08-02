<?php

namespace App\Providers;

use App\Contracts\AI\AIPredictionClient;
use App\Contracts\Blockchain\BlockchainClient;
use App\Contracts\Firebase\FirebaseDeviceAuth;
use App\Contracts\Firebase\FirebaseRealtimeClient;
use App\Contracts\Firebase\FirebaseTokenService;
use App\Services\AI\HttpAIPredictionClient;
use App\Services\AI\MockAIPredictionClient;
use App\Services\Blockchain\HttpBlockchainClient;
use App\Services\Blockchain\MockBlockchainClient;
use App\Services\Firebase\FirebaseAdminClient;
use App\Services\Firebase\FirebaseAdminDeviceAuth;
use App\Services\Firebase\MockFirebaseClient;
use App\Services\Firebase\MockFirebaseDeviceAuth;
use App\Services\Settings\SystemSettings;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class FishTraceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MockFirebaseClient::class);
        $this->app->singleton(FirebaseAdminClient::class);
        $this->app->singleton(FirebaseRealtimeClient::class, fn ($app) => $app->make(config('fishtrace.firebase.driver') === 'admin' ? FirebaseAdminClient::class : MockFirebaseClient::class));
        $this->app->alias(FirebaseRealtimeClient::class, FirebaseTokenService::class);
        $this->app->singleton(FirebaseDeviceAuth::class, fn ($app) => $app->make(config('fishtrace.firebase.driver') === 'admin' ? FirebaseAdminDeviceAuth::class : MockFirebaseDeviceAuth::class));
        $this->app->singleton(AIPredictionClient::class, fn ($app) => $app->make(config('fishtrace.ai.driver') === 'http' ? HttpAIPredictionClient::class : MockAIPredictionClient::class));
        $this->app->singleton(BlockchainClient::class, fn ($app) => $app->make(config('fishtrace.blockchain.driver') === 'http' ? HttpBlockchainClient::class : MockBlockchainClient::class));
    }

    public function boot(): void
    {
        View::composer('layouts.admin', function ($view): void {
            $view->with('platformName', app(SystemSettings::class)->get('platform_name'));
        });
    }
}
