<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sla:check-escalations')
    ->hourly()
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping();

Schedule::command('inactivity:check-thresholds')
    ->dailyAt('04:00')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping();

