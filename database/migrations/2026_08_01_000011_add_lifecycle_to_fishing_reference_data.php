<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fish_species', function (Blueprint $table): void {
            $table->index('is_active');
        });

        Schema::table('fishing_gear_types', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->index();
        });

        Schema::table('landing_sites', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->index();
        });
    }

    public function down(): void
    {
        Schema::table('landing_sites', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });

        Schema::table('fishing_gear_types', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });

        Schema::table('fish_species', function (Blueprint $table): void {
            $table->dropIndex(['is_active']);
        });
    }
};
