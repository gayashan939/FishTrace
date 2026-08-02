<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });
        Schema::create('organization_user', function (Blueprint $table): void {
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->primary(['organization_id', 'user_id']);
        });
        Schema::create('user_devices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_name');
            $table->string('platform')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->uuidMorphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
        Schema::create('password_reset_otps', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('email')->index();
            $table->string('otp_hash');
            $table->string('verification_token_hash')->nullable()->unique();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
        Schema::create('fish_species', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('common_name');
            $table->string('scientific_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('fishing_gear_types', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->timestamps();
        });
        Schema::create('landing_sites', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('district')->nullable();
            $table->timestamps();
        });
        Schema::create('boats', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained();
            $table->foreignUuid('owner_id')->constrained('users');
            $table->string('registration_number')->unique();
            $table->string('name');
            $table->string('type')->nullable();
            $table->decimal('capacity_kg', 12, 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('fishing_trips', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained();
            $table->foreignUuid('fisher_id')->constrained('users');
            $table->foreignUuid('boat_id')->constrained();
            $table->foreignUuid('landing_site_id')->nullable()->constrained();
            $table->string('trip_code')->unique();
            $table->string('status')->default('DRAFT')->index();
            $table->string('general_catch_area')->nullable();
            $table->timestamp('departed_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });
        Schema::create('catch_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained();
            $table->foreignUuid('fishing_trip_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('fish_species_id')->constrained();
            $table->foreignUuid('fishing_gear_type_id')->nullable()->constrained();
            $table->string('client_record_id')->nullable();
            $table->decimal('weight_kg', 12, 3);
            $table->unsignedInteger('quantity');
            $table->decimal('allocated_weight_kg', 12, 3)->default(0);
            $table->dateTime('caught_at');
            $table->timestamp('client_created_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'client_record_id']);
        });
        Schema::create('fish_batches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained();
            $table->foreignUuid('created_by')->constrained('users');
            $table->foreignUuid('fish_species_id')->constrained();
            $table->string('batch_code')->unique();
            $table->string('type')->default('RAW');
            $table->string('status')->default('CREATED')->index();
            $table->string('product_type')->default('Fresh fish');
            $table->decimal('total_weight_kg', 12, 3);
            $table->timestamp('created_from_catch_at')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_recalled')->default(false);
            $table->timestamps();
        });
        Schema::create('batch_catches', function (Blueprint $table): void {
            $table->foreignUuid('fish_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('catch_record_id')->constrained()->cascadeOnDelete();
            $table->decimal('allocated_weight_kg', 12, 3);
            $table->primary(['fish_batch_id', 'catch_record_id']);
        });
        Schema::create('qr_codes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('fish_batch_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('public_token', 80)->unique();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
        Schema::create('traceability_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('fish_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('organization_id')->nullable()->constrained();
            $table->foreignUuid('actor_id')->nullable()->constrained('users');
            $table->string('event_type');
            $table->string('title');
            $table->json('public_data')->nullable();
            $table->json('private_data')->nullable();
            $table->dateTime('occurred_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['traceability_events', 'qr_codes', 'batch_catches', 'fish_batches', 'catch_records', 'fishing_trips', 'boats', 'landing_sites', 'fishing_gear_types', 'fish_species', 'password_reset_otps', 'personal_access_tokens', 'user_devices', 'organization_user', 'role_user', 'roles', 'organizations'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
