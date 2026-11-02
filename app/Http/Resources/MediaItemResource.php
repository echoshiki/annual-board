<?php

namespace App\Http\Resources;

use App\Enums\MediaType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaItemResource extends JsonResource
{
    /**
     * 统一转换媒体条目输出结构（兼容 Eloquent Model 与数组）
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $type = data_get($this->resource, 'type');
        $mediaType = $type instanceof MediaType ? $type : MediaType::tryFrom((string) $type);

        return [
            'id' => data_get($this->resource, 'id'),
            'type' => $mediaType?->value ?? $type,
            'type_label' => $mediaType?->label(),
            'provider' => data_get($this->resource, 'provider'),
            'external_id' => (string) data_get($this->resource, 'external_id'),
            'title' => data_get($this->resource, 'title'),
            'original_title' => data_get($this->resource, 'original_title'),
            'subtitle' => data_get($this->resource, 'subtitle'),
            'release_year' => data_get($this->resource, 'release_year'),
            'cover_url' => data_get($this->resource, 'cover_url'),
            'rating' => data_get($this->resource, 'rating') !== null
                ? (float) data_get($this->resource, 'rating')
                : null,
            'extra_data' => data_get($this->resource, 'extra_data', []),
        ];
    }
}

