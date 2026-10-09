<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('report:send-daily')
    ->dailyAt(config('daily-report.time'))
    ->timezone(config('daily-report.timezone'))
    ->skip(fn () => now(config('daily-report.timezone'))->isSunday())
    ->withoutOverlapping();

// Hapus status (ala WhatsApp) yang sudah lewat 24 jam.
Schedule::call(fn () => \App\Models\UserStatus::where('expires_at', '<=', now())->delete())
    ->hourly()
    ->name('status:prune')
    ->withoutOverlapping();
