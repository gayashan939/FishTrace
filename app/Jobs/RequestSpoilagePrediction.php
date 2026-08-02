<?php

namespace App\Jobs;

use App\Enums\NotificationType;
use App\Models\AIServiceFailure;
use App\Models\FishBatch;
use App\Models\User;
use App\Services\AI\SpoilagePredictionService;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\OperationalNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RequestSpoilagePrediction implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public array $backoff = [30, 120, 300];

    public int $uniqueFor = 300;

    public function __construct(public string $batchId, public ?string $requestedById = null) {}

    public function uniqueId(): string
    {
        return $this->batchId;
    }

    public function handle(SpoilagePredictionService $service): void
    {
        $service->predict(FishBatch::findOrFail($this->batchId), $this->requestedById ? User::find($this->requestedById) : null);
    }

    public function failed(Throwable $exception): void
    {
        $batch = FishBatch::find($this->batchId);
        if ($batch === null) {
            return;
        }

        $message = 'AI prediction failed ('.class_basename($exception).').';
        $failure = AIServiceFailure::query()->where('fish_batch_id', $batch->id)->whereNull('resolved_at')->latest()->first();
        if ($failure === null) {
            $failure = AIServiceFailure::create(['fish_batch_id' => $batch->id, 'driver' => config('fishtrace.ai.driver'), 'error_message' => $message, 'attempts' => max(1, $this->attempts())]);
        } else {
            $failure->update(['error_message' => $message, 'attempts' => max($failure->attempts + 1, $this->attempts())]);
        }

        app(AuditLogger::class)->record('AI_PREDICTION_FAILED', $failure, null, ['driver' => config('fishtrace.ai.driver'), 'error_message' => $message, 'attempts' => $failure->attempts], organizationId: $batch->organization_id);
        app(OperationalNotifier::class)->organizationOnce('ai-service-failure:'.$failure->id, $batch->organization_id, NotificationType::AI_SERVICE_FAILURE, 'AI prediction unavailable', 'Spoilage-risk prediction could not be completed after retries.', ['batch_id' => $batch->id, 'failure_id' => $failure->id]);
    }
}
