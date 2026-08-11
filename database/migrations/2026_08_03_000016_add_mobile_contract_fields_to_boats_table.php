<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Additive migration for the active Flutter Boat workflow. */
    public function up(): void
    {
        Schema::table('boats', function (Blueprint $table): void {
            $table->decimal('length_meters', 8, 2)->nullable()->after('capacity_kg');
            $table->string('engine_details', 160)->nullable()->after('length_meters');
            $table->string('home_port', 120)->nullable()->after('engine_details');
        });
    }

    public function down(): void
    {
        Schema::table('boats', function (Blueprint $table): void {
            $table->dropColumn(['length_meters', 'engine_details', 'home_port']);
        });
    }
};
