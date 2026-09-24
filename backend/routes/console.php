<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Rumah Nafasy — Scheduler
|--------------------------------------------------------------------------
|
| Command yang berjalan otomatis berdasarkan jadwal.
| Production: setup cron * * * * * php artisan schedule:run
|
*/

// Every minute: release locked slots yang expired
Schedule::command('slots:release-locked')->everyMinute();

// Every 5 minutes: sync & expire pending payments (>24h)
Schedule::command('payments:sync-status')->everyFiveMinutes();

// Daily 09:00: H-1 reminder untuk konsultasi besok
Schedule::command('reminders:tomorrow')->dailyAt('09:00');

// Hourly at :00: H-1 jam reminder untuk konsultasi
Schedule::command('reminders:one-hour')->hourlyAt(0);

// Daily 09:00: reminder pasien yang belum pilih jadwal (H+3, H+6)
Schedule::command('orders:no-schedule-reminder')->dailyAt('10:00');

// Daily 00:01: auto-expire orders >7 hari + auto-refund
Schedule::command('orders:auto-expire')->dailyAt('00:01');

// Daily 08:00: daily report ke admin
Schedule::command('reports:daily-admin')->dailyAt('08:00');