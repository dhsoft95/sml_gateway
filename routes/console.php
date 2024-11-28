<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

(new Illuminate\Console\Scheduling\Schedule)->command('callbacks:process-retries')
    ->everyFiveMinutes()
    ->withoutOverlapping();

(new Illuminate\Console\Scheduling\Schedule)->command('callbacks:retry')
    ->everyFiveMinutes();

(new Illuminate\Console\Scheduling\Schedule)->command('inspire')
    ->hourly();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

