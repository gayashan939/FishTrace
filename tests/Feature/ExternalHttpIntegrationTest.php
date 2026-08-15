<?php

namespace Tests\Feature;

use App\Contracts\AI\AIPredictionClient;
use App\Contracts\Blockchain\BlockchainClient;
use App\Jobs\AnchorTraceabilityEvent;
use App\Jobs\RequestSpoilagePrediction;
use App\Models\AIServiceFailure;
use App\Models\BlockchainTransaction;
use App\Models\FishBatch;
use App\Models\TraceabilityEvent;
use App\Models\User;
use App\Notifications\OperationalNotification;
use App\Services\AI\HttpAIPredictionClient;
use App\Services\AI\SpoilagePredictionService;
use App\Services\Blockchain\HttpBlockchainClient;
use App\Services\Blockchain\TraceabilityAnchorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class ExternalHttpIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'fishtrace.ai.driver' => 'http',
            'fishtrace.ai.url' => 'https://ai.example.test',
            'fishtrace.ai.token' => 'ai-test-token',
            'fishtrace.ai.timeout' => 3,
            'fishtrace.blockchain.driver' => 'http',
            'fishtrace.blockchain.url' => 'https://chain.example.test',
            'fishtrace.blockchain.token' => 'chain-test-token',
            'fishtrace.blockchain.network' => 'testnet',
            'fishtrace.blockchain.contract' => '0x1234',
        ]);
    }

    public function test_http_ai_prediction_validates_contract_persists_result_and_notifies_high_risk(): void
    {
        $this->seed();
        Notification::fake();
        Http::fake(['https://ai.example.test/predict' => Http::response([
            'riskLevel' => 'HIGH',
            'confidence' => '0.94',
            'probabilities' => ['LOW' => '0.01', 'MEDIUM' => '0.05', 'HIGH' => '0.94'],
            'recommendation' => 'Inspect immediately.',
            'modelVersion' => '2026.08',
        ])]);
        $this->app->instance(AIPredictionClient::class, new HttpAIPredictionClient);
        $batch = FishBatch::firstOrFail();

        $prediction = app(SpoilagePredictionService::class)->predict($batch);

        $this->assertSame('HIGH', $prediction->risk_level);
        $this->assertSame('http', $prediction->provider);
        $this->assertSame(0.94, $prediction->confidence);
        $this->assertSame(['LOW' => 0.01, 'MEDIUM' => 0.05, 'HIGH' => 0.94], $prediction->probabilities);
        $this->assertDatabaseHas('ai_prediction_inputs', ['ai_prediction_id' => $prediction->id]);
        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://ai.example.test/predict'
                && $request->header('Authorization')[0] === 'Bearer ai-test-token'
                && array_key_exists('maximumProductTemperature', $request->data());
        });
        $recipient = User::where('email', 'fisher@fishtrace.demo')->firstOrFail();
        Notification::assertSentTo($recipient, OperationalNotification::class, fn (OperationalNotification $notification): bool => $notification->toArray($recipient)['type'] === 'HIGH_AI_RISK');
    }

    public function test_http_ai_client_rejects_malformed_probabilities_and_retries_connection_failures(): void
    {
        $client = new HttpAIPredictionClient;
        Http::fake(['*' => Http::response(['riskLevel' => 'HIGH', 'confidence' => 1.4, 'probabilities' => ['HIGH' => 1.4]])]);

        try {
            $client->predict([]);
            $this->fail('Malformed AI output was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The AI service returned an invalid prediction response.', $exception->getMessage());
        }

        Http::fake(fn () => Http::failedConnection('connection refused'));
        try {
            $client->predict([]);
            $this->fail('The failed AI connection did not throw.');
        } catch (ConnectionException) {
            $this->assertTrue(true);
        }
        Http::assertSentCount(2);
    }

    public function test_terminal_ai_failure_is_redacted_notified_and_resolved_by_a_later_success(): void
    {
        $this->seed();
        Notification::fake();
        $batch = FishBatch::firstOrFail();
        $job = new RequestSpoilagePrediction($batch->id);

        $job->failed(new RuntimeException('provider leaked secret-token-value'));

        $failure = AIServiceFailure::firstOrFail();
        $this->assertStringNotContainsString('secret-token-value', $failure->error_message);
        $this->assertDatabaseHas('audit_logs', ['action' => 'AI_PREDICTION_FAILED']);
        $recipient = User::where('email', 'fisher@fishtrace.demo')->firstOrFail();
        Notification::assertSentTo($recipient, OperationalNotification::class, fn (OperationalNotification $notification): bool => $notification->toArray($recipient)['type'] === 'AI_SERVICE_FAILURE');

        $this->app->instance(AIPredictionClient::class, new class implements AIPredictionClient
        {
            public function predict(array $features): array
            {
                return ['riskLevel' => 'LOW', 'confidence' => .9, 'probabilities' => ['LOW' => .9, 'MEDIUM' => .08, 'HIGH' => .02], 'recommendation' => 'Monitor.', 'modelVersion' => 'recovery-test', 'provider' => 'http'];
            }
        });
        $job->handle(app(SpoilagePredictionService::class));
        $this->assertNotNull($failure->fresh()->resolved_at);
    }

    public function test_http_blockchain_submission_and_verification_persist_only_safe_results(): void
    {
        $this->seed();
        Http::fake([
            'https://chain.example.test/anchors' => Http::response(['transactionReference' => 'tx/abc', 'status' => 'SUBMITTED', 'network' => 'testnet']),
            'https://chain.example.test/anchors/*' => Http::response(['verified' => true]),
        ]);
        $this->app->instance(BlockchainClient::class, new HttpBlockchainClient);
        $event = TraceabilityEvent::firstOrFail();
        $service = app(TraceabilityAnchorService::class);

        $transaction = $service->anchor($event);
        $this->assertTrue($service->verify($transaction));

        $transaction->refresh();
        $this->assertSame('CONFIRMED', $transaction->status);
        $this->assertNotNull($transaction->confirmed_at);
        $this->assertDatabaseHas('blockchain_verifications', ['blockchain_transaction_id' => $transaction->id, 'is_valid' => true]);
        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://chain.example.test/anchors'
                && $request->method() === 'POST'
                && in_array('Bearer chain-test-token', $request->header('Authorization'), true)
                && count($request->data()) === 3
                && isset($request['hash']);
        });
        Http::assertSent(fn ($request): bool => $request->method() === 'GET' && str_starts_with($request->url(), 'https://chain.example.test/anchors/tx'));
    }

    public function test_pending_blockchain_reservation_without_a_reference_is_resubmitted(): void
    {
        $this->seed();
        Http::fake([
            'https://chain.example.test/anchors' => Http::response(['transactionReference' => 'tx/recovered', 'status' => 'SUBMITTED', 'network' => 'testnet']),
        ]);
        $this->app->instance(BlockchainClient::class, new HttpBlockchainClient);
        $event = TraceabilityEvent::firstOrFail();
        $service = app(TraceabilityAnchorService::class);
        $transaction = BlockchainTransaction::query()->create([
            'event_hash' => $service->eventHash($event),
            'network' => 'testnet',
            'contract_address' => '0x1234',
            'status' => 'PENDING',
            'attempts' => 0,
        ]);

        $result = $service->anchor($event);

        $this->assertSame($transaction->id, $result->id);
        $this->assertSame('SUBMITTED', $result->status);
        $this->assertSame('tx/recovered', $result->transaction_reference);
        $this->assertSame(1, $result->attempts);
        Http::assertSentCount(1);
    }

    public function test_http_blockchain_client_rejects_invalid_responses_and_retries_connections(): void
    {
        $client = new HttpBlockchainClient;
        Http::fake(['*' => Http::response(['transactionReference' => '', 'status' => 'UNKNOWN'])]);

        try {
            $client->submit(str_repeat('a', 64));
            $this->fail('Malformed blockchain output was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The blockchain service returned an invalid submission response.', $exception->getMessage());
        }

        Http::fake(fn () => Http::failedConnection('connection refused'));
        try {
            $client->submit(str_repeat('a', 64));
            $this->fail('The failed blockchain connection did not throw.');
        } catch (ConnectionException) {
            $this->assertTrue(true);
        }
        Http::assertSentCount(2);
    }

    public function test_failed_http_blockchain_verification_is_persisted_and_notified_once(): void
    {
        $this->seed();
        Notification::fake();
        Http::fake([
            'https://chain.example.test/anchors' => Http::response(['transactionReference' => 'tx-unverified', 'status' => 'SUBMITTED', 'network' => 'testnet']),
            'https://chain.example.test/anchors/*' => Http::response(['verified' => false]),
        ]);
        $this->app->instance(BlockchainClient::class, new HttpBlockchainClient);
        $event = TraceabilityEvent::firstOrFail();
        $service = app(TraceabilityAnchorService::class);
        $transaction = $service->anchor($event);

        $this->assertFalse($service->verify($transaction));
        $this->assertFalse($service->verify($transaction));

        $this->assertDatabaseCount('blockchain_verifications', 2);
        $this->assertDatabaseHas('blockchain_verifications', ['blockchain_transaction_id' => $transaction->id, 'is_valid' => false]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'BLOCKCHAIN_VERIFICATION_FAILED']);
        $recipient = User::where('email', 'fisher@fishtrace.demo')->firstOrFail();
        Notification::assertSentToTimes($recipient, OperationalNotification::class, 1);
    }

    public function test_terminal_blockchain_failure_is_persisted_and_can_be_retried_idempotently(): void
    {
        config(['fishtrace.blockchain.driver' => 'mock', 'fishtrace.blockchain.network' => 'mock']);
        $this->seed();
        Notification::fake();
        $transactionCount = BlockchainTransaction::query()->count();
        $event = TraceabilityEvent::firstOrFail();
        $job = new AnchorTraceabilityEvent($event->id);

        $job->failed(new RuntimeException('provider leaked wallet-secret-value'));

        $transaction = BlockchainTransaction::firstOrFail();
        $this->assertSame('FAILED', $transaction->status);
        $this->assertStringNotContainsString('wallet-secret-value', (string) $transaction->error_message);
        $this->assertDatabaseHas('blockchain_event_anchors', ['traceability_event_id' => $event->id, 'blockchain_transaction_id' => $transaction->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'BLOCKCHAIN_ANCHOR_FAILED']);

        $this->app->forgetInstance(BlockchainClient::class);
        $job->handle(app(TraceabilityAnchorService::class));
        $transaction->refresh();
        $this->assertSame('CONFIRMED', $transaction->status);
        $this->assertSame(2, $transaction->attempts);
        $this->assertNull($transaction->error_message);
        $this->assertDatabaseCount('blockchain_transactions', $transactionCount);
        $this->assertDatabaseCount('blockchain_event_anchors', $transactionCount);
    }
}
