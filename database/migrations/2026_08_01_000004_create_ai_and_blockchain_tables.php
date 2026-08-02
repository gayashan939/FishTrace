<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_predictions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('fish_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('transport_trip_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('risk_level')->index();
            $table->decimal('confidence', 6, 5);
            $table->json('probabilities');
            $table->text('recommendation');
            $table->string('model_version');
            $table->string('provider');
            $table->dateTime('predicted_at');
            $table->timestamps();
        });
        Schema::create('ai_prediction_inputs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('ai_prediction_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('features');
            $table->timestamps();
        });
        Schema::create('ai_service_failures', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('fish_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('driver');
            $table->text('error_message');
            $table->unsignedInteger('attempts')->default(1);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
        Schema::create('blockchain_transactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('event_hash', 64)->unique();
            $table->string('transaction_reference')->nullable()->unique();
            $table->string('network');
            $table->string('contract_address')->nullable();
            $table->string('status')->default('PENDING')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('blockchain_event_anchors', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('traceability_event_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('blockchain_transaction_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('blockchain_verifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('blockchain_transaction_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_valid');
            $table->json('response')->nullable();
            $table->dateTime('verified_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blockchain_verifications');
        Schema::dropIfExists('blockchain_event_anchors');
        Schema::dropIfExists('blockchain_transactions');
        Schema::dropIfExists('ai_service_failures');
        Schema::dropIfExists('ai_prediction_inputs');
        Schema::dropIfExists('ai_predictions');
    }
};
