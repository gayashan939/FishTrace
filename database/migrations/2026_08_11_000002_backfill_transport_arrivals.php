<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('transport_trips')
            ->whereNull('arrived_at')
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('delivery_confirmations')
                    ->whereColumn('delivery_confirmations.transport_trip_id', 'transport_trips.id');
            })
            ->update([
                'arrived_at' => DB::raw('(SELECT delivered_at FROM delivery_confirmations WHERE delivery_confirmations.transport_trip_id = transport_trips.id LIMIT 1)'),
            ]);
    }

    public function down(): void
    {
        // Historical arrival evidence must not be erased during rollback.
    }
};
