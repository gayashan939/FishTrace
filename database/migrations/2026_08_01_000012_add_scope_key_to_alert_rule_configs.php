<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alert_rule_configs', function (Blueprint $table): void {
            $table->string('scope_key')->nullable()->after('organization_id');
        });

        DB::table('alert_rule_configs')->orderBy('created_at')->get(['id', 'organization_id', 'rule_type'])->each(function (object $rule): void {
            DB::table('alert_rule_configs')->where('id', $rule->id)->update(['scope_key' => ($rule->organization_id ?? 'GLOBAL').':'.$rule->rule_type]);
        });

        Schema::table('alert_rule_configs', function (Blueprint $table): void {
            $table->unique('scope_key');
        });
    }

    public function down(): void
    {
        Schema::table('alert_rule_configs', function (Blueprint $table): void {
            $table->dropUnique(['scope_key']);
            $table->dropColumn('scope_key');
        });
    }
};
