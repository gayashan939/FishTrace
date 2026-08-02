<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_trip_checklists', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('transport_trip_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('checklist_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pre_trip_checklist_id')->constrained()->cascadeOnDelete();
            $table->string('item_key', 60);
            $table->string('label');
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_completed')->default(false);
            $table->foreignUuid('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['pre_trip_checklist_id', 'item_key']);
        });

        Schema::create('transport_incidents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('transport_trip_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('reported_by')->constrained('users');
            $table->string('type', 80)->index();
            $table->string('severity', 20)->index();
            $table->text('description');
            $table->dateTime('occurred_at')->index();
            $table->timestamps();
        });

        Schema::create('delivery_confirmations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('transport_trip_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('confirmed_by')->constrained('users');
            $table->string('receiver_name', 120);
            $table->string('receiver_contact', 120)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('delivered_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_confirmations');
        Schema::dropIfExists('transport_incidents');
        Schema::dropIfExists('checklist_items');
        Schema::dropIfExists('pre_trip_checklists');
    }
};
