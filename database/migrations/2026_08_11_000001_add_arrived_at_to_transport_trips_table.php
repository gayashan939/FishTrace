<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transport_trips', function (Blueprint $table): void {
            $table->timestamp('arrived_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('transport_trips', function (Blueprint $table): void {
            $table->dropColumn('arrived_at');
        });
    }
};
