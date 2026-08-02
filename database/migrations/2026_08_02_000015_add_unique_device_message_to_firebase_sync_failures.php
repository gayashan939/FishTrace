<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firebase_sync_failures', function (Blueprint $table): void {
            $table->unique(['device_id', 'message_id'], 'firebase_sync_failures_device_message_unique');
        });
    }

    public function down(): void
    {
        Schema::table('firebase_sync_failures', function (Blueprint $table): void {
            $table->dropUnique('firebase_sync_failures_device_message_unique');
        });
    }
};
