<?php

namespace App\Console\Commands;

use App\Models\AccountListing;
use App\Models\Brand;
use App\Models\EventTheme;
use App\Models\FlashDeal;
use App\Models\Media;
use App\Models\PaymentMethod;
use App\Models\PopupBanner;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\MediaStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncMedia extends Command
{
    protected $signature = 'media:sync {--prune : Hapus record media yang tidak dipakai/dimiliki}';

    protected $description = 'Impor semua gambar ke tabel media agar tetap muncul di perangkat lain setelah clone';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $paths = $this->referencedPaths();

        $imported = 0;
        $already = 0;
        $noFile = 0;

        foreach ($paths as $path) {
            if ($path === null || $path === '') {
                continue;
            }

            if (MediaStore::find($path)) {
                $already++;
                continue;
            }

            if ($disk->exists($path) && MediaStore::import($path)) {
                $imported++;
            } else {
                $noFile++;
            }
        }

        // Impor semua file yang ada di folder storage tapi belum terdaftar di DB (backup/recovery).
        foreach ($disk->allFiles() as $file) {
            if (Str::startsWith($file, '.') || MediaStore::find($file)) {
                continue;
            }
            if (MediaStore::import($file)) {
                $imported++;
            }
        }

        $this->info("Media sinkron: {$imported} diimpor, {$already} sudah ada, {$noFile} dirujuk tapi file tidak ditemukan di storage.");

        if ($this->option('prune')) {
            $keep = [];
            foreach ($paths as $path) {
                $normalized = MediaStore::normalizePath($path);
                if ($normalized) {
                    $keep[$normalized] = true;
                }
            }

            $pruned = 0;
            foreach (Media::all() as $media) {
                if (isset($keep[$media->path]) || $disk->exists($media->path)) {
                    continue;
                }
                $media->delete();
                $pruned++;
            }

            $this->info("Prune: {$pruned} record media yatim dihapus.");
        }

        return self::SUCCESS;
    }

    protected function referencedPaths(): array
    {
        $paths = [];

        foreach (Product::all(['photo']) as $row) {
            $paths[] = $row->photo;
        }

        $fields = ['thumbnail', 'photo', 'detail_photo_1', 'detail_photo_2', 'detail_photo_3', 'detail_photo_4', 'detail_photo_5'];
        foreach (AccountListing::all($fields) as $row) {
            foreach ($fields as $field) {
                $paths[] = $row->{$field};
            }
        }

        $fields = ['photo', 'photo_light'];
        foreach (PaymentMethod::all($fields) as $row) {
            foreach ($fields as $field) {
                $paths[] = $row->{$field};
            }
        }

        $fields = ['icon', 'thumbnail', 'topup_character_image', 'featured_thumbnail', 'featured_img_1', 'featured_img_2', 'featured_img_3', 'carousel_bg', 'detail_bg'];
        foreach (Brand::all($fields) as $row) {
            foreach ($fields as $field) {
                $value = $row->{$field};
                $paths[] = $field === 'icon' && ! str_contains((string) $value, '/') ? null : $value;
            }
        }

        foreach (FlashDeal::all(['image']) as $row) {
            $paths[] = $row->image;
        }

        foreach (PopupBanner::all(['image']) as $row) {
            $paths[] = $row->image;
        }

        foreach (EventTheme::all(['logo_override', 'banner_image', 'decorative_images']) as $row) {
            $paths[] = $row->logo_override;
            $paths[] = $row->banner_image;
            foreach ((array) ($row->decorative_images ?: []) as $img) {
                $paths[] = $img;
            }
        }

        foreach (SiteSetting::all(['key', 'value', 'type']) as $row) {
            if ($row->type === 'image' || str_contains((string) $row->key, 'banner') || str_contains((string) $row->key, 'logo') || $row->key === 'qris_image') {
                $paths[] = $row->value;
            }
        }

        return array_values(array_unique(array_filter($paths)));
    }
}
