<?php

namespace App\Console\Commands;

use App\Models\AccountListing;
use App\Models\Brand;
use App\Models\PaymentMethod;
use App\Models\PopupBanner;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\ImageOptimizer;
use App\Services\MediaStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OptimizeImages extends Command
{
    protected $signature = 'images:optimize {--dry : Tampilkan apa yang akan dioptimalkan tanpa menulis}';

    protected $description = 'Re-optimalkan semua gambar yang sudah ter-upload (WebP + downscale)';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $dry = (bool) $this->option('dry');
        $remap = [];

        $optimized = 0;
        $savedBytes = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->definitions() as $definition) {
            foreach ($definition['rows'] as $row) {
                foreach ($definition['attrs'] as $attr => $max) {
                    $path = $row->{$attr};
                    [$maxWidth, $maxHeight] = $max;

                    if (!$path || Str::endsWith($path, '.svg')) {
                        continue;
                    }

                    if (isset($remap[$path])) {
                        $row->update([$attr => $remap[$path]]);
                        continue;
                    }

                    if (!$disk->exists($path)) {
                        continue;
                    }

                    MediaStore::import($path);

                    if ($this->isAlreadyOptimized($disk->path($path), $disk->size($path), $maxWidth, $maxHeight)) {
                        $skipped++;
                        continue;
                    }

                    $before = $disk->size($path);
                    $dryPath = null;

                    if ($dry) {
                        $dryPath = 'tmp-optimize/' . md5($path) . '-' . Str::random(6) . '.webp';
                        $newPath = ImageOptimizer::optimize($path, 'public', $maxWidth, $maxHeight, 512 * 1024, $dryPath);
                        $newSize = $disk->exists($dryPath) ? $disk->size($dryPath) : null;
                    } else {
                        $newPath = ImageOptimizer::optimize($path, 'public', $maxWidth, $maxHeight);
                        $newSize = $disk->exists($newPath) ? $disk->size($newPath) : null;
                    }

                    $changed = false;

                    if ($newSize === null) {
                        $skipped++;
                        continue;
                    }

                    if ($newPath !== $path || $newSize !== $before) {
                        $changed = true;
                    }

                    if (!$changed) {
                        $skipped++;
                        continue;
                    }

                    $this->info(sprintf('  %-16s %s (%s -> %s)', $row::class, $path, $this->humanBytes($before), $this->humanBytes($newSize)));

                    $optimized++;
                    $savedBytes += max(0, $before - $newSize);

                    if ($dry) {
                        $disk->delete($dryPath);
                        continue;
                    }

                    if (Str::replace('.webp', '', $newPath) !== Str::replace('.webp', '', $path)) {
                        MediaStore::delete($path);
                    }

                    $remap[$path] = $newPath;
                    $row->update([$attr => $newPath]);
                    MediaStore::import($newPath);

                    if ($row instanceof SiteSetting) {
                        Cache::forget("setting_{$row->key}");
                    }
                }
            }
        }

        $this->newLine();

        if ($dry) {
            $this->line("{$optimized} file butuh optimasi, {$skipped} sudah optimal, {$failed} gagal.");
            return self::SUCCESS;
        }

        $this->info("Selesai: {$optimized} dioptimalkan (+{$skipped} sudah optimal, {$failed} gagal).");
        $this->info('Hemat ' . $this->humanBytes($savedBytes));

        return self::SUCCESS;
    }

    protected function isAlreadyOptimized(string $absolutePath, int $size, int $maxWidth, int $maxHeight): bool
    {
        if (!Str::endsWith($absolutePath, '.webp') || $size > 512 * 1024) {
            return false;
        }

        $dim = @getimagesize($absolutePath);

        return $dim !== false && $dim[0] <= $maxWidth && $dim[1] <= $maxHeight;
    }

    protected function humanBytes(int $bytes): string
    {
        $bytes = max(0, $bytes);

        return $bytes >= 1024 * 1024
            ? round($bytes / (1024 * 1024), 2) . ' MB'
            : round($bytes / 1024, 1) . ' KB';
    }

    protected function definitions(): array
    {
        $settingKeys = [
            'site_logo' => [512, 512],
            'site_hero_banner' => [1920, 750],
            'site_hero_banner_2' => [1920, 750],
            'site_hero_banner_3' => [1920, 750],
            'jba_hero_banner' => [1920, 750],
            'jba_hero_banner_2' => [1920, 750],
            'jba_hero_banner_3' => [1920, 750],
        ];

        return [
            [
                'rows' => Product::all(),
                'attrs' => ['photo' => [800, 800]],
            ],
            [
                'rows' => AccountListing::all(),
                'attrs' => [
                    'photo' => [1600, 1600],
                    'detail_photo_1' => [1600, 1600],
                    'detail_photo_2' => [1600, 1600],
                    'detail_photo_3' => [1600, 1600],
                    'detail_photo_4' => [1600, 1600],
                ],
            ],
            [
                'rows' => PaymentMethod::all(),
                'attrs' => [
                    'photo' => [400, 400],
                    'photo_light' => [400, 400],
                ],
            ],
            [
                'rows' => Brand::all(),
                'attrs' => [
                    'thumbnail' => [640, 640],
                    'jba_card_image' => [960, 960],
                    'featured_thumbnail' => [1280, 1280],
                    'featured_img_1' => [1280, 1280],
                    'featured_img_2' => [1280, 1280],
                    'featured_img_3' => [1280, 1280],
                    'carousel_bg' => [1920, 1080],
                    'detail_bg' => [1920, 1080],
                ],
            ],
            [
                'rows' => PopupBanner::all(),
                'attrs' => ['image' => [1200, 1600]],
            ],
            [
                'rows' => SiteSetting::whereIn('key', array_keys($settingKeys))->get(),
                'attrs' => $settingKeys,
            ],
        ];
    }
}
