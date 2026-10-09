<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = [
        'name',
        'icon',
        'thumbnail',
        'topup_character_image',
        'featured_thumbnail',
        'featured_img_1',
        'featured_img_2',
        'featured_img_3',
        'carousel_bg',
        'detail_bg',
        'detail_bg_position',
        'category',
        'service_type',
        'catalog_group',
        'requires_zone_id',
        'description',
        'is_active',
        'is_popular',
        'is_topup_popular',
        'topup_popular_image',
        'topup_popular_logo',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'is_topup_popular' => 'boolean',
        'requires_zone_id' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['thumbnail_url', 'topup_character_image_url', 'featured_thumbnail_url', 'featured_img_urls', 'carousel_bg_url', 'detail_bg_url', 'topup_popular_image_url', 'topup_popular_logo_url'];

    public function getTopupCharacterImageUrlAttribute(): ?string
    {
        return $this->topup_character_image ? media_url($this->topup_character_image) : null;
    }

    public function getTopupPopularImageUrlAttribute(): ?string
    {
        return $this->topup_popular_image ? media_url($this->topup_popular_image) : null;
    }

    public function getTopupPopularLogoUrlAttribute(): ?string
    {
        return $this->topup_popular_logo ? media_url($this->topup_popular_logo) : null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail) {
            return media_url($this->thumbnail);
        }

        return null;
    }

    public function getFeaturedThumbnailUrlAttribute(): ?string
    {
        if ($this->featured_thumbnail) {
            return media_url($this->featured_thumbnail);
        }

        return null;
    }

    public function getFeaturedImgUrlsAttribute(): array
    {
        $urls = [];
        foreach (['featured_img_1', 'featured_img_2', 'featured_img_3'] as $col) {
            if ($this->$col) {
                $urls[] = media_url($this->$col);
            }
        }

        return $urls;
    }

    public function getCarouselBgUrlAttribute(): ?string
    {
        if ($this->carousel_bg) {
            return media_url($this->carousel_bg);
        }

        return null;
    }

    public function getDetailBgUrlAttribute(): ?string
    {
        if ($this->detail_bg) {
            return media_url($this->detail_bg);
        }

        return null;
    }
}
