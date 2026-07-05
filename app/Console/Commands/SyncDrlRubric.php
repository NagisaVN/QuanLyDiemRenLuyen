<?php

namespace App\Console\Commands;

use App\Support\DrlRubric;
use Illuminate\Console\Command;

class SyncDrlRubric extends Command
{
    protected $signature = 'drl:sync-rubric';

    protected $description = 'Sync the conduct evaluation rubric from the DRL template';

    public function handle(): int
    {
        DrlRubric::sync();

        $this->info('DRL rubric synced successfully.');

        return self::SUCCESS;
    }
}
