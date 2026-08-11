<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $legacyA = DB::table('quality_grades')->where('code', 'GRADE-A')->first();
        if ($legacyA !== null && ! DB::table('quality_grades')->where('code', 'A')->exists()) {
            DB::table('quality_grades')->where('id', $legacyA->id)->update([
                'code' => 'A',
                'updated_at' => $now,
            ]);
        }

        foreach ([
            ['id' => '69dfa458-b82b-4d59-ab41-8b41de3d0a02', 'code' => 'B', 'name' => 'Grade B', 'rank' => 2],
            ['id' => '24fb296a-fe9d-4425-b2e8-e9acb438dc03', 'code' => 'C', 'name' => 'Grade C', 'rank' => 3],
        ] as $grade) {
            DB::table('quality_grades')->updateOrInsert(
                ['code' => $grade['code']],
                $grade + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        DB::table('quality_grades')->whereIn('code', ['B', 'C'])->update(['is_active' => false]);
    }
};
