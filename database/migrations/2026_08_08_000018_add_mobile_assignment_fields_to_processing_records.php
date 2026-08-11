<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('processing_records', function (Blueprint $table): void {
            $table->string('operator_name', 120)->nullable()->after('processing_type_id');
            $table->string('processing_area', 120)->nullable()->after('operator_name');
        });
    }

    public function down(): void
    {
        Schema::table('processing_records', function (Blueprint $table): void {
            $table->dropColumn(['operator_name', 'processing_area']);
        });
    }
};
