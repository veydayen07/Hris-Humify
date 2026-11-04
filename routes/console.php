<?php

use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('attendance:close-day')
    ->dailyAt(config('humify.close_day_time', '01:00'))
    ->timezone(config('humify.company_timezone', 'Asia/Jakarta'));
