<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sinkronisasi rutin listing Jual Beli Akun dari johengaming.id.
// Aktif bila cron `php artisan schedule:run` dipasang di server.
Schedule::command('jba:sync-johengaming')->dailyAt('02:30');

/*
 * Rekonsiliasi topup Digiflazz.
 *
 * WAJIB aktifkan cron scheduler di server:
 *   * * * * * cd /path/proyek && php artisan schedule:run >> /dev/null 2>&1
 *
 * Tanpa command ini, order yang webhook Digiflazz-nya tidak sampai akan
 * menggantung selamanya di status "processing" dan saldonya tidak pernah
 * dikembalikan ke user.
 */
Schedule::command('digiflazz:reconcile')->everyFiveMinutes()->withoutOverlapping();

/*
 * Settlement checkout keranjang berjalan lewat queue (butuh `queue:work`).
 * Command ini hanya jaring pengaman: kalau worker sempat mati, order anak yang
 * menggantung di status "pending" akan diantrikan ulang.
 */
Schedule::command('checkout:retry')->everyTenMinutes()->withoutOverlapping();
