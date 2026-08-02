<?php

namespace App\Console\Commands;

use App\Services\IoT\OfflineDeviceMonitor;
use Illuminate\Console\Command;

class CheckOfflineDevices extends Command
{
    protected $signature = 'fishtrace:check-offline-devices {--minutes= : Override the configured offline duration}';

    protected $description = 'Notify transporter organizations about assigned devices that stopped reporting';

    public function handle(OfflineDeviceMonitor $monitor): int
    {
        $option = $this->option('minutes');
        $override = is_numeric($option) ? max(1, (int) $option) : null;
        $count = $monitor->check($override);
        $this->info('Checked '.$count.' active device assignments.');

        return self::SUCCESS;
    }
}
