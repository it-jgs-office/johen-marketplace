<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageOptimizer
{
    protected const DEFAULT_QUALITY = 86;

    protected const MIN_QUALITY = 55;

    protected const CROP_MAX_BYTES = 768 * 1024;

    protected const MAX_GD_MEMORY_BYTES = 768 * 1024 * 1024;

    /**
     * Simpan file upload, optimalkan (downscale + WebP) lalu kembalikan path relatif.
     */
    public static function storeOptimized(
        UploadedFile $file,
        string $dir,
        int $maxWidth = 1200,
        int $maxHeight = 1600,
        int $maxBytes = 512 * 1024
    ): string {
        $path = $file->store($dir, 'public');

        try {
            $optimized = static::optimize($path, 'public', $maxWidth, $maxHeight, $maxBytes);
        } catch (\Throwable $e) {
            report($e);
            $optimized = $path;
        }

        if ($optimized !== $path) {
            Storage::disk('public')->delete($path);
        }

        MediaStore::import($optimized);

        return $optimized;
    }

    /**
     * Cek apakah GD + WebP tersedia di PHP server saat ini.
     */
    protected static function gdAvailable(): bool
    {
        return extension_loaded('gd')
            && function_exists('imagecreatefromstring')
            && function_exists('imagewebp');
    }

    /**
     * GD harus mendekode seluruh bitmap sebelum resize. Siapkan memory headroom
     * berdasarkan jumlah piksel agar gambar kamera beresolusi tinggi dapat
     * langsung diperkecil tanpa berhenti karena memory_limit bawaan 256 MB.
     */
    protected static function ensureGdMemory(string $absolutePath): void
    {
        $info = @getimagesize($absolutePath);
        if (!is_array($info)) {
            return;
        }

        $width = max(1, (int) ($info[0] ?? 1));
        $height = max(1, (int) ($info[1] ?? 1));
        $currentUsage = memory_get_usage(true);

        // Delapan byte per piksel memberi ruang untuk bitmap GD, overhead
        // decoder, serta kanvas output 1920px yang jauh lebih kecil.
        $required = $currentUsage + ($width * $height * 8) + (32 * 1024 * 1024);
        $currentLimit = static::memoryLimitBytes((string) ini_get('memory_limit'));

        if ($currentLimit === -1 || $required <= $currentLimit) {
            return;
        }

        $requested = (int) (ceil($required / (64 * 1024 * 1024)) * 64 * 1024 * 1024);
        if ($requested > static::MAX_GD_MEMORY_BYTES) {
            throw new \RuntimeException('Resolusi gambar terlalu besar untuk diproses. Gunakan gambar maksimal sekitar 8000 x 8000 piksel.');
        }

        @ini_set('memory_limit', (string) (int) ceil($requested / 1024 / 1024) . 'M');
        $newLimit = static::memoryLimitBytes((string) ini_get('memory_limit'));

        if ($newLimit !== -1 && $newLimit < $required) {
            throw new \RuntimeException('Server tidak memiliki memori yang cukup untuk mengompres gambar ini.');
        }
    }

    protected static function memoryLimitBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return -1;
        }

        $unit = strtolower(substr($value, -1));
        $bytes = (float) $value;

        return (int) match ($unit) {
            'g' => $bytes * 1024 * 1024 * 1024,
            'm' => $bytes * 1024 * 1024,
            'k' => $bytes * 1024,
            default => $bytes,
        };
    }

    /**
     * Buka sumber gambar GD dari path storage yang sudah ada (jpeg/png/webp/gif).
     */
    protected static function openPath(string $absolutePath): ?\GdImage
    {
        static::ensureGdMemory($absolutePath);
        $info = @getimagesize($absolutePath);
        $mime = $info['mime'] ?? null;

        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($absolutePath),
            'image/png' => @imagecreatefrompng($absolutePath),
            'image/webp' => @imagecreatefromwebp($absolutePath),
            'image/gif' => @imagecreatefromgif($absolutePath),
            default => null,
        };

        return $img instanceof \GdImage ? $img : null;
    }

    /**
     * Downscale proporsional tanpa distorsi, re-encode ke WebP dengan kualitas adaptif.
     * Tidak pernah upscale dan tidak memotong gambar (keep aspect / contain).
     *
     * @return string path relatif storage (bisa .webp baru), atau path awal bila gagal
     */
    public static function optimize(
        string $diskPath,
        string $disk = 'public',
        int $maxWidth = 1200,
        int $maxHeight = 1600,
        int $maxBytes = 512 * 1024,
        ?string $outputPath = null
    ): string {
        $disk = Storage::disk($disk);

        if (! static::gdAvailable()) {
            return $diskPath;
        }

        $source = static::openPath($disk->path($diskPath));

        if ($source === null) {
            return $diskPath;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1.0, $maxWidth / max(1, $width), $maxHeight / max(1, $height));

        if ($scale < 1.0) {
            $newWidth = max(1, (int) round($width * $scale));
            $newHeight = max(1, (int) round($height * $scale));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($source);

            $source = $resized;
        }

        imagepalettetotruecolor($source);
        imagealphablending($source, true);
        imagesavealpha($source, true);

        $dirName = pathinfo($diskPath, PATHINFO_DIRNAME);
        $baseName = pathinfo($diskPath, PATHINFO_FILENAME);
        $webpPath = $outputPath ?? trim($dirName . '/' . $baseName . '.webp', './');
        $webpAbsolute = $disk->path($webpPath);

        $outputDir = dirname($webpAbsolute);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $inputSize = @filesize($disk->path($diskPath)) ?: 0;

        $quality = static::DEFAULT_QUALITY;
        $fallback = null;

        for (; $quality >= static::MIN_QUALITY; $quality -= 10) {
            if (!ob_start()) {
                break;
            }
            imagewebp($source, null, $quality);
            $buffer = ob_get_clean();

            if ($buffer === false) {
                continue;
            }

            $size = strlen($buffer);
            if ($size >= $inputSize) {
                continue;
            }

            if ($size <= $maxBytes) {
                file_put_contents($webpAbsolute, $buffer);
                imagedestroy($source);

                return ltrim(str_replace('\\', '/', $webpPath), '/');
            }

            if ($fallback === null || $size < $fallback[1]) {
                $fallback = [$buffer, $size];
            }
        }

        if ($fallback !== null) {
            file_put_contents($webpAbsolute, $fallback[0]);
            imagedestroy($source);

            return ltrim(str_replace('\\', '/', $webpPath), '/');
        }

        imagedestroy($source);

        return $diskPath;
    }

    /**
     * Buka sumber gambar GD dari file upload (jpeg/png/webp).
     */
    protected static function open(UploadedFile $file): ?\GdImage
    {
        static::ensureGdMemory($file->getRealPath());
        $mime = $file->getMimeType();

        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($file->getRealPath()),
            'image/png' => @imagecreatefrompng($file->getRealPath()),
            'image/webp' => @imagecreatefromwebp($file->getRealPath()),
            default => null,
        };
    }

    /**
     * Resize & crop (pendekatan cover) langsung dari sumber ke kanvas output.
     * Tidak membuat salinan bitmap ukuran penuh agar upload resolusi besar tetap
     * hemat memori. Hasil JPG dikompres adaptif sampai mendekati batas ukuran.
     *
     * @return string path relatif storage (mis. brands/bg/xxxx.jpg)
     */
    public static function optimizeAndCrop(
        UploadedFile $file,
        string $ratio,
        int $maxWidth = 1920,
        int $quality = 82,
        int $maxBytes = self::CROP_MAX_BYTES
    ): string {
        $src = self::open($file);
        if (!$src) {
            $fallback = $file->store('brands/bg', 'public');
            MediaStore::import($fallback);

            return $fallback;
        }

        // Parse rasio (mis. "2:1", "21:9")
        $parts = array_map('floatval', explode(':', $ratio));
        $targetRatio = ($parts[1] ?? 0) > 0 ? $parts[0] / $parts[1] : 2.0;

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        $srcRatio = $srcW / max(1, $srcH);

        // Tentukan area crop pada bitmap sumber, lalu resample sekali saja ke
        // ukuran akhir. Peak memory = bitmap sumber + kanvas output kecil.
        if ($srcRatio > $targetRatio) {
            $sourceH = $srcH;
            $sourceW = max(1, (int) round($srcH * $targetRatio));
            $sourceX = max(0, (int) round(($srcW - $sourceW) / 2));
            $sourceY = 0;
        } else {
            $sourceW = $srcW;
            $sourceH = max(1, (int) round($srcW / max(0.01, $targetRatio)));
            $sourceX = 0;
            $sourceY = max(0, (int) round(($srcH - $sourceH) / 2));
        }

        $dstW = max(1, min($maxWidth, $sourceW));
        $dstH = max(1, (int) round($dstW / max(0.01, $targetRatio)));
        $canvas = imagecreatetruecolor($dstW, $dstH);

        // JPG tidak mendukung alpha. Putihkan hanya kanvas output yang kecil,
        // bukan membuat duplikat gambar sumber beresolusi penuh.
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled(
            $canvas,
            $src,
            0,
            0,
            $sourceX,
            $sourceY,
            $dstW,
            $dstH,
            $sourceW,
            $sourceH
        );
        imagedestroy($src);

        $name = Str::random(40) . '.jpg';
        $dir = storage_path('app/public/brands/bg');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $absoluteOutput = $dir . DIRECTORY_SEPARATOR . $name;
        $quality = max(self::MIN_QUALITY, min(92, $quality));

        for ($currentQuality = $quality; $currentQuality >= self::MIN_QUALITY; $currentQuality -= 7) {
            imagejpeg($canvas, $absoluteOutput, $currentQuality);
            clearstatcache(true, $absoluteOutput);

            if ((@filesize($absoluteOutput) ?: 0) <= $maxBytes) {
                break;
            }
        }

        imagedestroy($canvas);

        $path = 'brands/bg/' . $name;
        MediaStore::import($path);

        return $path;
    }
}
