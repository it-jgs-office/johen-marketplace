<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountListing extends Model
{
    protected $fillable = [
        'game',
        'thumbnail',
        'photo',
        'detail_photo_1',
        'detail_photo_2',
        'detail_photo_3',
        'detail_photo_4',
        'video_url',
        'product_name',
        'specifications',
        'owner_name',
        'price',
        'original_price',
        'whatsapp',
        'login_credentials',
        'is_active',
        'promo_type',
        'discount_percent',
        'collector_tier',
        'deal_type',
        'is_sold',
        'source',
        'source_id',
        'source_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_sold' => 'boolean',
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount_percent' => 'integer',
        'login_credentials' => 'encrypted',
    ];

    protected $hidden = [
        'login_credentials',
    ];

    protected $appends = [
        'thumbnail_url',
        'photo_url',
        'detail_photo_urls',
        'youtube_video_id',
    ];

    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail) {
            return media_url($this->thumbnail);
        }
        return null;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo) {
            return media_url($this->photo);
        }
        return null;
    }

    public function getDetailPhotoUrlsAttribute(): array
    {
        $urls = [];
        for ($i = 1; $i <= 4; $i++) {
            $col = "detail_photo_{$i}";
            if ($this->$col) {
                $urls[] = media_url($this->$col);
            }
        }
        return $urls;
    }

    public function getYoutubeVideoIdAttribute(): ?string
    {
        if (!$this->video_url) return null;

        $patterns = [
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
            '/youtu\.be\/([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $this->video_url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
