<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->string('vehicle_type', 80)->nullable()->after('name');
            $table->string('refrigeration_category', 80)->nullable()->after('vehicle_type');
            $table->decimal('capacity_tonnes', 8, 3)->nullable()->after('refrigeration_category');
            $table->string('reefer_unit', 120)->nullable()->after('capacity_tonnes');
            $table->decimal('min_temperature_celsius', 6, 2)->nullable()->after('reefer_unit');
            $table->decimal('max_temperature_celsius', 6, 2)->nullable()->after('min_temperature_celsius');
            $table->string('default_driver_name', 120)->nullable()->after('max_temperature_celsius');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropColumn(['vehicle_type', 'refrigeration_category', 'capacity_tonnes', 'reefer_unit', 'min_temperature_celsius', 'max_temperature_celsius', 'default_driver_name']);
        });
    }
};
