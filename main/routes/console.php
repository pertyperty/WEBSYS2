<?php

use App\Actions\RecordOperation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn (RecordOperation $record) => $record->handle('scheduler'))
    ->everyFiveMinutes()->name('checkpoint:scheduler-heartbeat')->withoutOverlapping();
Schedule::command('checkpoint:reconcile-compliance')->dailyAt('00:05')->timezone('Asia/Manila')->withoutOverlapping();
Schedule::command('checkpoint:verify-backup')->dailyAt('01:00')->timezone('Asia/Manila')->withoutOverlapping();
Schedule::command('checkpoint:operations-status')->everyFifteenMinutes()->withoutOverlapping();
