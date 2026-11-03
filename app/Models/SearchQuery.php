<?php

namespace App\Models;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    protected $fillable = [
        'type',
        'keyword',
        'provider',
        'search_count',
        'api_request_count',
        'result_count',
        'last_hit_source',
        'last_status',
        'last_duration_ms',
        'last_error',
        'last_api_requested_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'search_count' => 'integer',
            'api_request_count' => 'integer',
            'result_count' => 'integer',
            'last_duration_ms' => 'integer',
            'last_api_requested_at' => 'datetime',
        ];
    }

    /**
     * 记录或更新一次搜索词统计与第三方 API 调用状态
     */
    public static function recordSearch(
        MediaType $type,
        string $normalizedKeyword,
        int $resultCount,
        string $hitSource,
        ?string $provider = null,
        bool $calledRemoteApi = false,
        ?int $durationMs = null,
        ?string $status = null,
        ?string $error = null
    ): self {
        $query = static::query()->firstOrNew([
            'type' => $type->value,
            'keyword' => $normalizedKeyword,
        ]);

        $query->search_count = ($query->search_count ?? 0) + 1;
        $query->result_count = $resultCount;
        $query->last_hit_source = $hitSource;

        if ($provider !== null) {
            $query->provider = $provider;
        }

        // 仅当本次真正向第三方 API 发起了请求时，更新 API 请求计数、耗时、状态与时间
        if ($calledRemoteApi) {
            $query->api_request_count = ($query->api_request_count ?? 0) + 1;
            $query->last_duration_ms = $durationMs;
            $query->last_status = $status ?? ($error ? 'error' : ($resultCount > 0 ? 'success' : 'empty'));
            $query->last_error = $error;
            $query->last_api_requested_at = now();
        } elseif (! $query->exists) {
            $query->last_status = $status ?? ($resultCount > 0 ? 'success' : 'empty');
        }

        $query->save();

        return $query;
    }
}

