<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fishing_trips', function (Blueprint $table): void {
            $table->uuid('client_record_id')->nullable()->unique()->after('trip_code');
            $table->dateTime('planned_departure_at')->nullable()->after('general_catch_area');
            $table->decimal('expected_duration_hours', 6, 2)->nullable()->after('planned_departure_at');
            $table->decimal('fishing_area_latitude', 10, 7)->nullable()->after('expected_duration_hours');
            $table->decimal('fishing_area_longitude', 10, 7)->nullable()->after('fishing_area_latitude');
            $table->text('notes')->nullable()->after('fishing_area_longitude');
        });

        Schema::create('fishing_trip_crew_members', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('fishing_trip_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->timestamps();
            $table->unique(['fishing_trip_id', 'name']);
        });

        Schema::table('catch_records', function (Blueprint $table): void {
            $table->string('condition', 20)->nullable()->after('quantity');
            $table->decimal('latitude', 10, 7)->nullable()->after('condition');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->text('notes')->nullable()->after('longitude');
        });

        Schema::table('fish_batches', function (Blueprint $table): void {
            $table->foreignUuid('fishing_trip_id')->nullable()->after('fish_species_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('fish_count')->default(0)->after('total_weight_kg');
            $table->string('quality_grade', 20)->nullable()->after('fish_count');
            $table->decimal('storage_temperature_celsius', 6, 2)->nullable()->after('quality_grade');
            $table->string('ice_type', 30)->nullable()->after('storage_temperature_celsius');
            $table->decimal('ice_amount_kg', 10, 3)->nullable()->after('ice_type');
            $table->string('landing_site_name', 120)->nullable()->after('ice_amount_kg');
            $table->text('notes')->nullable()->after('landing_site_name');
        });
    }

    public function down(): void
    {
        Schema::table('fish_batches', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('fishing_trip_id');
            $table->dropColumn(['fish_count', 'quality_grade', 'storage_temperature_celsius', 'ice_type', 'ice_amount_kg', 'landing_site_name', 'notes']);
        });
        Schema::table('catch_records', function (Blueprint $table): void {
            $table->dropColumn(['condition', 'latitude', 'longitude', 'notes']);
        });
        Schema::dropIfExists('fishing_trip_crew_members');
        Schema::table('fishing_trips', function (Blueprint $table): void {
            $table->dropUnique(['client_record_id']);
            $table->dropColumn(['client_record_id', 'planned_departure_at', 'expected_duration_hours', 'fishing_area_latitude', 'fishing_area_longitude', 'notes']);
        });
    }
};
