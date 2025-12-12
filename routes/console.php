<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;

// Example command (default)
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule the shift auto-close command at 11:59 PM daily
app(Schedule::class)->command('shift:auto-close')->dailyAt('23:59')->timezone('Asia/Karachi');