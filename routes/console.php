<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('subscriptions:check-expiry')->daily();

// Fetch AWS and Firebase Bills on the 1st of every month at midnight
Schedule::command('costs:fetch-cloud')->monthlyOn(1, '00:00');
