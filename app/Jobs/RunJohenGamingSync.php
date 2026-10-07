<?php

namespace App\Jobs;

use App\Services\JohenGamingSyncService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RunJohenGamingSync
{
    use Dispatchable;

    public const STATUS_KEY = 'johengaming:account-listings:sync-status';

    public const RUNNING_KEY = 'johengaming:account-listings:sync-running';

    public function __construct(public ?string $game = null)
    {
    }

    public function handle(JohenGamingSyncService $service): void
    {
        set_time_limit(0);

        try {
            $result = $service->sync($this->game);

            Cache::put(self::STATUS_KEY, [
                'state' => $result['errors'] > 0 ? 'failed' : 'completed',
                'finished_at' => now()->toDateTimeString(),
                'result' => $result,
            ], now()->addDay());

            if ($result['errors'] > 0) {
                Log::warning('JohenGaming account listing sync finished with errors', ['result' => $result]);
            }
        } catch (\Throwable $e) {
            Log::error('JohenGaming account listing sync failed', ['exception' => $e]);
            Cache::put(self::STATUS_KEY, [
                'state' => 'failed',
                'finished_at' => now()->toDateTimeString(),
                'message' => $e->getMessage(),
            ], now()->addDay());
        } finally {
            Cache::forget(self::RUNNING_KEY);
        }
    }
}
