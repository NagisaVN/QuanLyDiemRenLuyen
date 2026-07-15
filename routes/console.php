<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:database')->dailyAt('01:00')->withoutOverlapping()->onOneServer();
Schedule::command('evaluations:sync-statuses')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
Schedule::command('notifications:reconcile')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();
Schedule::command('activities:auto-status')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
