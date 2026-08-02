<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rule_configs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('rule_type');
            $table->decimal('warning_threshold', 10, 3)->nullable();
            $table->decimal('critical_threshold', 10, 3)->nullable();
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'rule_type']);
        });
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->uuidMorphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('alert_acknowledgements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cold_chain_alert_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained();
            $table->text('note')->nullable();
            $table->dateTime('acknowledged_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_acknowledgements');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('alert_rule_configs');
    }
};
