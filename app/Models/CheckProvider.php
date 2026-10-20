<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CheckProvider extends Model
{
    protected $fillable = [
        'brand_pattern',
        'label',
        'free_type',
        'free_config',
        'fallback_free_type',
        'fallback_free_config',
        'digiflazz_fallback_enabled',
        'digiflazz_sku',
        'enabled',
        'priority',
    ];

    protected $casts = [
        'free_config' => 'array',
        'fallback_free_config' => 'array',
        'digiflazz_fallback_enabled' => 'boolean',
        'enabled' => 'boolean',
        'priority' => 'integer',
    ];

    /** Kosongkan cache daftar provider setelah perubahan dari admin. */
    public static function flushCache(): void
    {
        Cache::forget('check_providers_all');
    }
}
