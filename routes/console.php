<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
Schedule::command('digiflazz:reconcile')->everyMinute()->withoutOverlapping();

/*
 * Perbarui katalog, harga, stok, dan status produk game dari Digiflazz.
 * Refresh paksa dipakai supaya setiap jadwal selalu membaca price list terbaru,
 * bukan cache katalog sebelumnya.
 */
Schedule::command('digiflazz:sync --force')->everyTenMinutes()->withoutOverlapping();

/*
 * Settlement checkout keranjang berjalan lewat queue (butuh `queue:work`).
 * Command ini hanya jaring pengaman: kalau worker sempat mati, order anak yang
 * menggantung di status "pending" akan diantrikan ulang.
 */
Schedule::command('checkout:retry')->everyTenMinutes()->withoutOverlapping();

// Keep confirming DOKU payments even after the customer closes the payment page.
Schedule::command('doku:reconcile')->everyMinute()->withoutOverlapping();

// Tutup otomatis pembayaran topup yang tidak dibayar sampai batas waktunya.
Schedule::command('payments:expire')->everyMinute()->withoutOverlapping();
