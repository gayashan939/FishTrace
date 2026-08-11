<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_lots', function (Blueprint $table): void {
            $table->decimal('default_unit_price', 12, 2)->nullable()->after('sold_packages');
            $table->decimal('low_stock_threshold_kg', 10, 3)->nullable()->after('default_unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_lots', function (Blueprint $table): void {
            $table->dropColumn(['default_unit_price', 'low_stock_threshold_kg']);
        });
    }
};
