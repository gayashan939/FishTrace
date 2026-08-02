<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->index(['type', 'is_active']);
        });
        Schema::table('organization_user', function (Blueprint $table): void {
            $table->index(['user_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::table('organization_user', fn (Blueprint $table) => $table->dropIndex(['user_id', 'is_primary']));
        Schema::table('organizations', fn (Blueprint $table) => $table->dropIndex(['type', 'is_active']));
    }
};
