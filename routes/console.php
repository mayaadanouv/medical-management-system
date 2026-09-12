<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
// تشغيل الأمر كل دقيقة ليفحص المواعيد القادمة بعد ساعتين بالضبط
Schedule::command('appointments:send-hourly-reminders')->everyMinute();
