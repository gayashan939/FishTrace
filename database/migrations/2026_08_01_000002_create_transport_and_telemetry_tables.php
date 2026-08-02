<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained();
            $table->string('registration_number')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('iot_devices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained();
            $table->string('device_code')->unique();
            $table->string('serial_number')->unique();
            $table->string('display_name');
            $table->string('status')->default('ACTIVE')->index();
            $table->string('firmware_version')->nullable();
            $table->decimal('battery_percentage', 5, 2)->nullable();
            $table->smallInteger('signal_strength')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('firebase_uid')->nullable()->unique();
            $table->string('firebase_email')->nullable();
            $table->boolean('firebase_auth_enabled')->default(false);
            $table->unsignedInteger('credential_version')->default(0);
            $table->timestamp('last_token_refresh_at')->nullable();
            $table->boolean('supports_product_temperature')->default(true);
            $table->boolean('supports_air_temperature')->default(true);
            $table->boolean('supports_humidity')->default(true);
            $table->boolean('supports_gps')->default(true);
            $table->boolean('supports_door_sensor')->default(true);
            $table->timestamps();
        });
        Schema::create('transport_trips', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained();
            $table->foreignUuid('created_by')->constrained('users');
            $table->foreignUuid('vehicle_id')->constrained();
            $table->string('trip_code')->unique();
            $table->string('driver_name');
            $table->string('status')->default('DRAFT')->index();
            $table->string('origin');
            $table->string('destination');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('transport_batches', function (Blueprint $table): void {
            $table->foreignUuid('transport_trip_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('fish_batch_id')->constrained()->cascadeOnDelete();
            $table->primary(['transport_trip_id', 'fish_batch_id']);
        });
        Schema::create('device_assignments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('iot_device_id')->constrained();
            $table->foreignUuid('transport_trip_id')->constrained();
            $table->string('status')->default('ACTIVE')->index();
            $table->string('firebase_sync_status')->default('PENDING')->index();
            $table->text('firebase_sync_error')->nullable();
            $table->dateTime('assigned_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->index(['iot_device_id', 'status']);
        });
        Schema::create('sensor_readings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('message_id')->unique();
            $table->foreignUuid('iot_device_id')->constrained();
            $table->foreignUuid('transport_trip_id')->constrained();
            $table->decimal('product_temperature', 7, 3)->nullable();
            $table->decimal('air_temperature', 7, 3)->nullable();
            $table->decimal('humidity', 5, 2)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('speed_kph', 7, 2)->nullable();
            $table->decimal('battery_percentage', 5, 2)->nullable();
            $table->smallInteger('signal_strength')->nullable();
            $table->boolean('door_open')->default(false);
            // DATETIME avoids MariaDB's legacy multi-TIMESTAMP default restriction.
            // FishTrace stores all application timestamps in UTC.
            $table->dateTime('recorded_at')->index();
            $table->dateTime('imported_at');
            $table->json('raw_payload');
            $table->timestamps();
            $table->index(['iot_device_id', 'recorded_at']);
            $table->index(['transport_trip_id', 'recorded_at']);
        });
        Schema::create('firebase_sync_cursors', function (Blueprint $table): void {
            $table->foreignUuid('device_id')->primary()->constrained('iot_devices')->cascadeOnDelete();
            $table->timestamp('last_recorded_at')->nullable();
            $table->string('last_message_id')->nullable();
            $table->timestamp('last_successful_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
        Schema::create('firebase_sync_failures', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('device_id')->nullable()->constrained('iot_devices')->nullOnDelete();
            $table->string('firebase_uid')->nullable();
            $table->string('message_id')->index();
            $table->json('payload');
            $table->string('error_code');
            $table->text('error_message');
            $table->unsignedInteger('retry_count')->default(0);
            $table->dateTime('first_failed_at');
            $table->dateTime('last_failed_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
        Schema::create('cold_chain_alerts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('transport_trip_id')->constrained();
            $table->foreignUuid('fish_batch_id')->nullable()->constrained();
            $table->string('type');
            $table->string('severity')->index();
            $table->string('status')->default('OPEN')->index();
            $table->decimal('measured_value', 10, 3)->nullable();
            $table->decimal('threshold_value', 10, 3)->nullable();
            $table->dateTime('first_detected_at');
            $table->dateTime('last_detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'severity']);
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->nullableUuidMorphs('auditable');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->uuid('request_id')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'cold_chain_alerts', 'firebase_sync_failures', 'firebase_sync_cursors', 'sensor_readings', 'device_assignments', 'transport_batches', 'transport_trips', 'iot_devices', 'vehicles'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
