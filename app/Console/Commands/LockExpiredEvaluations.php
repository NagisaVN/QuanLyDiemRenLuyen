<?php

namespace App\Console\Commands;

use App\Services\DotDanhGiaService;
use Illuminate\Console\Command;

class LockExpiredEvaluations extends Command
{
    protected $signature = 'evaluations:lock-expired';

    protected $description = 'Lock evaluation forms after the GVCN review deadline has passed';

    public function handle(DotDanhGiaService $service): int
    {
        $count = $service->lockExpiredForms();

        $this->info("Locked {$count} expired evaluation form(s).");

        return self::SUCCESS;
    }
}
