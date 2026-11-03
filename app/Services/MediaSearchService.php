<?php

namespace App\Services;

use App\Contracts\MediaProviderInterface;
use App\Enums\MediaType;
use App\Models\MediaItem;
use App\Models\SearchQuery;
use App\Services\MediaProviders\BangumiProvider;
use App\Services\MediaProviders\MusicBrainzProvider;
use App\Services\MediaProviders\TmdbProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class MediaSearchService
{
    /**
     * 搜索词缓存时间（默认 7 天）
     */
    protected int $cacheTtlSeconds = 604800;

    /**
     * 执行搜索：优先查缓存 -> 未命中则请求第三方 API 并入库+写缓存 -> 异常则降级查本地库
     *
     * @return array{items: Collection<int, MediaItem>, hit_source: string, provider: ?string, duration_ms: ?int, search_query: ?SearchQuery, error: ?string}
     */
    public function search(MediaType $type, string $keyword, int $limit = 20, bool $forceRefresh = false): array
    {
        $normalizedKeyword = mb_strtolower(trim($keyword));
        $cacheKey = sprintf('media_search:%s:%s', $type->value, md5($normalizedKeyword));

        // 1. 优先命中搜索词缓存（Redis / Database Cache）
        if (! $forceRefresh && Cache::has($cacheKey)) {
            $cachedIds = Cache::get($cacheKey);
            if (is_array($cachedIds) && ! empty($cachedIds)) {
                $itemsById = MediaItem::query()
                    ->whereIn('id', $cachedIds)
                    ->get()
                    ->keyBy('id');

                // 按缓存时的原始相关性排序还原
                $orderedItems = collect($cachedIds)
                    ->map(fn ($id) => $itemsById->get($id))
                    ->filter()
                    ->values();

                if ($orderedItems->isNotEmpty()) {
                    $providerName = $orderedItems->first()?->provider;
                    $searchQuery = $this->safeRecordSearch(
                        type: $type,
                        normalizedKeyword: $normalizedKeyword,
                        resultCount: $orderedItems->count(),
                        hitSource: 'cache',
                        provider: $providerName,
                        calledRemoteApi: false
                    );

                    return [
                        'items' => $orderedItems,
                        'hit_source' => 'cache',
                        'provider' => $providerName,
                        'duration_ms' => null,
                        'search_query' => $searchQuery,
                        'error' => null,
                    ];
                }
            }
        }

        // 2. 获取对应类型的第三方数据源驱动
        $provider = $this->resolveProvider($type);
        $apiError = null;
        $durationMs = null;

        if ($provider !== null) {
            $startTime = hrtime(true);

            try {
                $remoteResults = $provider->search($type, $keyword, $limit);
                $durationMs = (int) round((hrtime(true) - $startTime) / 1_000_000);

                if (! empty($remoteResults)) {
                    $savedItems = $this->persistItems($remoteResults);

                    // 将本次搜索词对应的本地主键 ID 列表写入缓存
                    Cache::put(
                        $cacheKey,
                        $savedItems->pluck('id')->all(),
                        $this->cacheTtlSeconds
                    );

                    $searchQuery = $this->safeRecordSearch(
                        type: $type,
                        normalizedKeyword: $normalizedKeyword,
                        resultCount: $savedItems->count(),
                        hitSource: 'remote_api',
                        provider: $provider->providerName(),
                        calledRemoteApi: true,
                        durationMs: $durationMs,
                        status: 'success'
                    );

                    return [
                        'items' => $savedItems,
                        'hit_source' => 'remote_api',
                        'provider' => $provider->providerName(),
                        'duration_ms' => $durationMs,
                        'search_query' => $searchQuery,
                        'error' => null,
                    ];
                }

                // 如果第三方 API 正常返回但无匹配结果，也尝试查一下本地是否有手动录入的条目
                $localItems = $this->queryLocalDatabase($type, $keyword, $limit);
                $hitSource = $localItems->isNotEmpty() ? 'database' : 'remote_api_empty';

                $searchQuery = $this->safeRecordSearch(
                    type: $type,
                    normalizedKeyword: $normalizedKeyword,
                    resultCount: $localItems->count(),
                    hitSource: $hitSource,
                    provider: $provider->providerName(),
                    calledRemoteApi: true,
                    durationMs: $durationMs,
                    status: 'empty'
                );

                return [
                    'items' => $localItems,
                    'hit_source' => $hitSource,
                    'provider' => $provider->providerName(),
                    'duration_ms' => $durationMs,
                    'search_query' => $searchQuery,
                    'error' => null,
                ];
            } catch (Throwable $e) {
                $durationMs = (int) round((hrtime(true) - $startTime) / 1_000_000);
                $apiError = $e->getMessage();

                Log::warning('第三方媒体搜索接口调用失败，已自动降级为本地数据库查询', [
                    'type' => $type->value,
                    'provider' => $provider->providerName(),
                    'keyword' => $keyword,
                    'duration_ms' => $durationMs,
                    'error' => $apiError,
                ]);
            }
        }

        // 3. 兜底降级：查询本地数据库
        $fallbackItems = $this->queryLocalDatabase($type, $keyword, $limit);
        $hitSource = $provider ? 'database_fallback' : 'database';

        $searchQuery = $this->safeRecordSearch(
            type: $type,
            normalizedKeyword: $normalizedKeyword,
            resultCount: $fallbackItems->count(),
            hitSource: $hitSource,
            provider: $provider?->providerName(),
            calledRemoteApi: $provider !== null,
            durationMs: $durationMs,
            status: $apiError ? 'error' : null,
            error: $apiError
        );

        return [
            'items' => $fallbackItems,
            'hit_source' => $hitSource,
            'provider' => $provider?->providerName(),
            'duration_ms' => $durationMs,
            'search_query' => $searchQuery,
            'error' => $apiError,
        ];
    }

    /**
     * 安全写入搜索词统计（即使统计表未迁移或异常也不阻塞主搜索流程）
     */
    protected function safeRecordSearch(
        MediaType $type,
        string $normalizedKeyword,
        int $resultCount,
        string $hitSource,
        ?string $provider = null,
        bool $calledRemoteApi = false,
        ?int $durationMs = null,
        ?string $status = null,
        ?string $error = null
    ): ?SearchQuery {
        try {
            return SearchQuery::recordSearch(
                type: $type,
                normalizedKeyword: $normalizedKeyword,
                resultCount: $resultCount,
                hitSource: $hitSource,
                provider: $provider,
                calledRemoteApi: $calledRemoteApi,
                durationMs: $durationMs,
                status: $status,
                error: $error
            );
        } catch (Throwable $e) {
            Log::warning('记录搜索词统计失败', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * 根据媒体类型解析对应的第三方 API 驱动
     */
    protected function resolveProvider(MediaType $type): ?MediaProviderInterface
    {
        return match ($type) {
            MediaType::MOVIE, MediaType::TV => app(TmdbProvider::class),
            MediaType::MUSIC => app(MusicBrainzProvider::class),
            MediaType::GAME => app(BangumiProvider::class),
        };
    }

    /**
     * 将第三方返回的标准化数组批量更新或写入 media_items 表
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return Collection<int, MediaItem>
     */
    protected function persistItems(array $rows): Collection
    {
        $saved = collect();

        foreach ($rows as $row) {
            $item = MediaItem::query()->updateOrCreate(
                [
                    'type' => $row['type'],
                    'provider' => $row['provider'],
                    'external_id' => (string) $row['external_id'],
                ],
                [
                    'title' => $row['title'],
                    'original_title' => $row['original_title'] ?? null,
                    'subtitle' => $row['subtitle'] ?? null,
                    'release_year' => $row['release_year'] ?? null,
                    'cover_url' => $row['cover_url'] ?? null,
                    'rating' => $row['rating'] ?? null,
                    'extra_data' => $row['extra_data'] ?? [],
                ]
            );

            $saved->push($item);
        }

        return $saved;
    }

    /**
     * 本地数据库模糊查询
     *
     * @return Collection<int, MediaItem>
     */
    protected function queryLocalDatabase(MediaType $type, string $keyword, int $limit): Collection
    {
        return MediaItem::query()
            ->searchLocal($type, $keyword)
            ->orderByDesc('rating')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }
}
