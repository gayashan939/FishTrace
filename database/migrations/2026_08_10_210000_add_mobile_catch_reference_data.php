<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SPECIES = [
        ['id' => 'c3245789-7c46-4b37-8310-29f10f76dd01', 'common_name' => 'Indian Mackerel', 'scientific_name' => 'Rastrelliger kanagurta'],
        ['id' => '7b69e44e-95f5-4a8d-bcef-ec230af85302', 'common_name' => 'Seer Fish', 'scientific_name' => 'Scomberomorus commerson'],
        ['id' => 'a11e2f86-e0e5-47c7-981c-1e68f82cdf03', 'common_name' => 'Squid', 'scientific_name' => 'Loligo duvaucelii'],
    ];

    private const GEAR_TYPES = [
        ['id' => 'bb43db7d-dd97-423e-91f7-f85580f15a01', 'name' => 'Gill net'],
        ['id' => 'e4e20a1d-c118-41af-b385-d13b33f53702', 'name' => 'Trawl'],
        ['id' => '7be0a8c3-30a4-4e49-b72a-c374e89bab03', 'name' => 'Handline'],
    ];

    public function up(): void
    {
        $now = now();
        foreach (self::SPECIES as $species) {
            $existing = DB::table('fish_species')->where('common_name', $species['common_name'])->first();
            if ($existing !== null) {
                DB::table('fish_species')->where('id', $existing->id)->update([
                    'scientific_name' => $species['scientific_name'],
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
                continue;
            }
            DB::table('fish_species')->insert($species + [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (self::GEAR_TYPES as $gear) {
            $existing = DB::table('fishing_gear_types')->where('name', $gear['name'])->first();
            if ($existing !== null) {
                DB::table('fishing_gear_types')->where('id', $existing->id)->update([
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
                continue;
            }
            DB::table('fishing_gear_types')->insert($gear + [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Keep referenced historical records intact if this data migration is
        // rolled back; only remove them from future mobile selections.
        DB::table('fishing_gear_types')->whereIn('id', array_column(self::GEAR_TYPES, 'id'))->update(['is_active' => false]);
        DB::table('fish_species')->whereIn('id', array_column(self::SPECIES, 'id'))->update(['is_active' => false]);
    }
};
