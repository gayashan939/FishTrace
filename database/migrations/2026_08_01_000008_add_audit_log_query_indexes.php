<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->index(['organization_id', 'created_at'], 'audit_org_created_idx');
            $table->index(['user_id', 'created_at'], 'audit_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex('audit_org_created_idx');
            $table->dropIndex('audit_user_created_idx');
        });
    }
};
