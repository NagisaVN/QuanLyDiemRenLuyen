<?php

namespace App\Console\Commands;

use App\Services\DotDanhGiaService;
use Illuminate\Console\Command;

class SyncEvaluationStatuses extends Command
{
    protected $signature = 'evaluations:sync-statuses';

    protected $description = 'Synchronize evaluation periods and forms with their configured UTC schedule';

    public function handle(DotDanhGiaService $service): int
    {
        $result = $service->syncAll();

        $this->info("Synchronized {$result['periods']} period(s), locked {$result['locked']} form(s), published {$result['published']} period(s).");

        return self::SUCCESS;
    }
}
