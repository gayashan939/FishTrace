<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retail_sales', function (Blueprint $table): void {
            $table->char('request_fingerprint', 64)->nullable()->after('client_reference');
        });
    }

    public function down(): void
    {
        Schema::table('retail_sales', function (Blueprint $table): void {
            $table->dropColumn('request_fingerprint');
        });
    }
};
