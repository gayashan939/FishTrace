<?php

declare(strict_types=1);

use App\Actions\Retail\RecordRetailSale;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$application = require dirname(__DIR__, 2).'/bootstrap/app.php';
$application->make(Kernel::class)->bootstrap();

[$script, $userId, $locationId, $lotId, $clientReference] = $argv;

try {
    app(RecordRetailSale::class)->execute(User::findOrFail($userId), [
        'retail_location_id' => $locationId,
        'client_reference' => $clientReference,
        'items' => [[
            'inventory_lot_id' => $lotId,
            'quantity' => 1,
            'unit_price' => 1000,
        ]],
    ]);

    fwrite(STDOUT, 'SOLD');
} catch (Throwable) {
    fwrite(STDOUT, 'REJECTED');
}
