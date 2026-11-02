<?php

namespace App\Models;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MediaItem extends Model
{
    protected $fillable = [
        'type',
        'provider',
        'external_id',
        'title',
        'original_title',
        'subtitle',
        'release_year',
        'cover_url',
        'rating',
        'extra_data',
    ];

    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'rating' => 'float',
            'extra_data' => 'array',
        ];
    }

    /**
     * 本地数据库模糊查询作用域（用于缓存兜底或本地检索）
     */
    public function scopeSearchLocal(Builder $query, MediaType|string $type, string $keyword): Builder
    {
        $typeValue = $type instanceof MediaType ? $type->value : $type;

        return $query->where('type', $typeValue)
            ->where(function (Builder $q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('original_title', 'like', "%{$keyword}%")
                    ->orWhere('subtitle', 'like', "%{$keyword}%");
            });
    }
}

