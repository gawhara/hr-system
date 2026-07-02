<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('hr:send-expiry-alerts')->dailyAt('07:00');

if (config('hr.sync.role') === 'branch') {
    Schedule::command('hr:sync')->everyFifteenMinutes()->withoutOverlapping();
}
