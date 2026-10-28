<?php

namespace App\Console\Commands;

use App\Services\DigiflazzService;
use Illuminate\Console\Command;

class SyncDigiflazzProducts extends Command
{
    protected $signature = 'digiflazz:sync {--force : Lewati cache price list (ambil data fresh dari API)}';

    protected $description = 'Sync products from Digiflazz price list';

    public function handle(DigiflazzService $digiflazz): int
    {
        $force = (bool) $this->option('force');

        $this->info('Fetching products from Digiflazz'.($force ? ' (force refresh)...' : '...'));

        $result = $digiflazz->syncProducts($force);

        if ($result['success']) {
            $this->info($result['message']);

            return self::SUCCESS;
        }

        $this->error($result['message']);

        return self::FAILURE;
    }
}
