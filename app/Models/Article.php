<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'category',
        'featured_image',
        'excerpt',
        'content',
        'meta_title',
        'meta_description',
        'focus_keyword',
        'status',
        'is_featured',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! empty($this->attributes['api_image_url'])) {
            return $this->attributes['api_image_url'];
        }

        return $this->featured_image ? media_url($this->featured_image) : null;
    }

    public function getReadingTimeAttribute(): int
    {
        if (isset($this->attributes['api_reading_time'])) {
            return max(1, (int) $this->attributes['api_reading_time']);
        }

        return max(1, (int) ceil(str_word_count(strip_tags($this->content)) / 200));
    }
}
