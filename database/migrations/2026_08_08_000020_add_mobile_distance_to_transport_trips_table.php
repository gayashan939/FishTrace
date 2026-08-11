<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transport_trips', function (Blueprint $table): void {
            $table->decimal('estimated_distance_km', 10, 2)->nullable()->after('destination');
        });
    }

    public function down(): void
    {
        Schema::table('transport_trips', function (Blueprint $table): void {
            $table->dropColumn('estimated_distance_km');
        });
    }
};
