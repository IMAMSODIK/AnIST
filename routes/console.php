<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 |--------------------------------------------------------------------------
 | Background queue runner for shared hosting (Hostinger)
 |--------------------------------------------------------------------------
 |
 | Shared hosting tidak mendukung Supervisor / worker persisten, jadi kita
 | jalankan queue worker via cron setiap menit. Worker memproses semua job
 | pada queue "evidence" lalu berhenti sendiri (--stop-when-empty) dengan
 | batas waktu 55 detik agar tidak overlap dengan cron berikutnya.
 |
 | Setup cron di hPanel Hostinger (lihat instruksi di chat):
 |   * * * * * cd /home/uXXXXXX/kpi_advisor && php artisan schedule:run >> /dev/null 2>&1
 |
 */
Schedule::command('queue:work', [
    '--queue' => 'evidence',
    '--stop-when-empty' => true,
    '--max-time' => 55,
    '--tries' => 2,
    '--delay' => 10,
    '--memory' => 128,
])->everyMinute()
    ->name('evidence-queue-worker');

Schedule::command('queue:work', [
    '--queue' => 'default',
    '--stop-when-empty' => true,
    '--max-time' => 55,
    '--tries' => 2,
    '--delay' => 10,
    '--memory' => 128,
])->everyMinute()
    ->name('default-queue-worker');
