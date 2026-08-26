<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::command('winner-gym:subscriptions-refresh')
    ->dailyAt('00:05')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

Schedule::command('winner-gym:whatsapp-process')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('winner-gym:backup')
    ->dailyAt('02:00')
    ->timezone(config('app.timezone'))
    ->when(fn (): bool => (bool) config('winner-gym.backups.automated'))
    ->withoutOverlapping();
