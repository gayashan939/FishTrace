<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processing_types', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('quality_grades', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedTinyInteger('rank');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('processor_profiles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('facility_name');
            $table->string('license_number')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('batch_intakes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('fish_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('processor_organization_id')->constrained('organizations');
            $table->foreignUuid('received_by')->constrained('users');
            $table->string('status')->index();
            $table->decimal('received_weight_kg', 12, 3)->nullable();
            $table->string('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('received_at');
            $table->timestamps();
            $table->unique(['fish_batch_id', 'processor_organization_id']);
        });
        Schema::create('processing_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('fish_batch_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('batch_intake_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('organization_id')->constrained();
            $table->foreignUuid('created_by')->constrained('users');
            $table->foreignUuid('processing_type_id')->nullable()->constrained();
            $table->string('status')->default('IN_PROGRESS')->index();
            $table->decimal('input_weight_kg', 12, 3);
            $table->decimal('output_weight_kg', 12, 3)->nullable();
            $table->decimal('waste_weight_kg', 12, 3)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('processing_steps', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('processing_record_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->unsignedTinyInteger('sequence');
            $table->string('status')->default('PENDING')->index();
            $table->json('measurements')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['processing_record_id', 'type']);
            $table->unique(['processing_record_id', 'sequence']);
        });
        Schema::create('quality_inspections', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('fish_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('processing_record_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('organization_id')->constrained();
            $table->foreignUuid('inspector_id')->constrained('users');
            $table->foreignUuid('quality_grade_id')->nullable()->constrained();
            $table->string('result')->index();
            $table->decimal('product_temperature', 7, 3);
            $table->decimal('ph_level', 4, 2)->nullable();
            $table->string('appearance');
            $table->string('odor');
            $table->text('notes')->nullable();
            $table->dateTime('inspected_at');
            $table->timestamps();
        });
        Schema::create('child_batches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_batch_id')->constrained('fish_batches')->cascadeOnDelete();
            $table->foreignUuid('child_batch_id')->unique()->constrained('fish_batches')->cascadeOnDelete();
            $table->decimal('allocated_weight_kg', 12, 3);
            $table->foreignUuid('created_by')->constrained('users');
            $table->timestamps();
        });
        Schema::create('batch_relationships', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_batch_id')->constrained('fish_batches')->cascadeOnDelete();
            $table->foreignUuid('child_batch_id')->unique()->constrained('fish_batches')->cascadeOnDelete();
            $table->string('relationship_type')->default('SPLIT');
            $table->timestamps();
        });
        Schema::create('package_labels', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('fish_batch_id')->constrained()->cascadeOnDelete();
            $table->string('label_code')->unique();
            $table->string('public_token', 80)->unique();
            $table->decimal('package_weight_kg', 12, 3);
            $table->unsignedInteger('package_count')->default(1);
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['package_labels', 'batch_relationships', 'child_batches', 'quality_inspections', 'processing_steps', 'processing_records', 'batch_intakes', 'processor_profiles', 'quality_grades', 'processing_types'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
