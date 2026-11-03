<?php

namespace App\Services\MediaProviders;

use App\Contracts\MediaProviderInterface;
use App\Enums\MediaType;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TmdbProvider implements MediaProviderInterface
{
    public function providerName(): string
    {
        return 'tmdb';
    }

    /**
     * 调用 TMDB 搜索电影或剧集
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(MediaType $type, string $keyword, int $limit = 20): array
    {
        $endpoint = match ($type) {
            MediaType::MOVIE => '/search/movie',
            MediaType::TV => '/search/tv',
            default => throw new RuntimeException("TMDB 驱动不支持该媒体类型: {$type->value}"),
        };

        $queryParams = [
            'query' => $keyword,
            'language' => 'zh-CN',
            'include_adult' => false,
            'page' => 1,
        ];

        // 如果未配置 Bearer Token 但配置了 API Key，则通过 query 参数传递
        $token = config('services.tmdb.token');
        $apiKey = config('services.tmdb.key');

        if (empty($token) && ! empty($apiKey)) {
            $queryParams['api_key'] = $apiKey;
        }

        $response = $this->httpClient()->get($endpoint, $queryParams);

        $response->throw();

        $results = $response->json('results', []);
        if (! is_array($results)) {
            return [];
        }

        $items = [];
        foreach (array_slice($results, 0, $limit) as $row) {
            if (! is_array($row) || empty($row['id'])) {
                continue;
            }

            $items[] = $type === MediaType::MOVIE
                ? $this->normalizeMovie($row)
                : $this->normalizeTv($row);
        }

        return $items;
    }

    /**
     * 构建带鉴权与超时配置的 HTTP 客户端
     */
    protected function httpClient(): PendingRequest
    {
        $baseUrl = rtrim((string) config('services.tmdb.base_url', 'https://api.themoviedb.org/3'), '/');
        $timeout = (int) config('services.tmdb.timeout', 10);
        $token = config('services.tmdb.token');
        $apiKey = config('services.tmdb.key');

        if (empty($token) && empty($apiKey)) {
            throw new RuntimeException('未配置 TMDB_API_TOKEN 或 TMDB_API_KEY，请检查 .env 文件');
        }

        $client = Http::baseUrl($baseUrl)
            ->timeout($timeout)
            ->acceptJson();

        if (! empty($token)) {
            $client->withToken($token);
        }

        return $client;
    }

    /**
     * 标准化电影数据 (Movie)
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalizeMovie(array $row): array
    {
        $title = trim((string) ($row['title'] ?? $row['original_title'] ?? '未知电影'));
        $originalTitle = trim((string) ($row['original_title'] ?? ''));
        $releaseDate = (string) ($row['release_date'] ?? '');
        $releaseYear = strlen($releaseDate) >= 4 ? substr($releaseDate, 0, 4) : null;
        $overview = trim((string) ($row['overview'] ?? ''));

        // 副标题优先展示不同于中文名的原名，否则展示简介摘要
        $subtitle = ($originalTitle !== '' && $originalTitle !== $title)
            ? $originalTitle
            : ($overview !== '' ? mb_strimwidth($overview, 0, 50, '...') : null);

        return [
            'type' => MediaType::MOVIE->value,
            'provider' => $this->providerName(),
            'external_id' => (string) $row['id'],
            'title' => $title,
            'original_title' => $originalTitle !== '' ? $originalTitle : null,
            'subtitle' => $subtitle,
            'release_year' => $releaseYear,
            'cover_url' => $this->buildImageUrl($row['poster_path'] ?? null),
            'rating' => isset($row['vote_average']) && $row['vote_average'] > 0
                ? round((float) $row['vote_average'], 1)
                : null,
            'extra_data' => [
                'release_date' => $releaseDate ?: null,
                'overview' => $overview ?: null,
                'backdrop_url' => $this->buildImageUrl($row['backdrop_path'] ?? null),
                'genre_ids' => $row['genre_ids'] ?? [],
                'vote_count' => $row['vote_count'] ?? 0,
                'popularity' => $row['popularity'] ?? 0,
            ],
        ];
    }

    /**
     * 标准化剧集数据 (TV)
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalizeTv(array $row): array
    {
        $title = trim((string) ($row['name'] ?? $row['original_name'] ?? '未知剧集'));
        $originalTitle = trim((string) ($row['original_name'] ?? ''));
        $firstAirDate = (string) ($row['first_air_date'] ?? '');
        $releaseYear = strlen($firstAirDate) >= 4 ? substr($firstAirDate, 0, 4) : null;
        $overview = trim((string) ($row['overview'] ?? ''));

        $subtitle = ($originalTitle !== '' && $originalTitle !== $title)
            ? $originalTitle
            : ($overview !== '' ? mb_strimwidth($overview, 0, 50, '...') : null);

        return [
            'type' => MediaType::TV->value,
            'provider' => $this->providerName(),
            'external_id' => (string) $row['id'],
            'title' => $title,
            'original_title' => $originalTitle !== '' ? $originalTitle : null,
            'subtitle' => $subtitle,
            'release_year' => $releaseYear,
            'cover_url' => $this->buildImageUrl($row['poster_path'] ?? null),
            'rating' => isset($row['vote_average']) && $row['vote_average'] > 0
                ? round((float) $row['vote_average'], 1)
                : null,
            'extra_data' => [
                'first_air_date' => $firstAirDate ?: null,
                'origin_country' => $row['origin_country'] ?? [],
                'overview' => $overview ?: null,
                'backdrop_url' => $this->buildImageUrl($row['backdrop_path'] ?? null),
                'genre_ids' => $row['genre_ids'] ?? [],
                'vote_count' => $row['vote_count'] ?? 0,
                'popularity' => $row['popularity'] ?? 0,
            ],
        ];
    }

    /**
     * 拼接 TMDB 海报完整 URL
     */
    protected function buildImageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $imageBaseUrl = rtrim((string) config('services.tmdb.image_base_url', 'https://image.tmdb.org/t/p/w500'), '/');

        return $imageBaseUrl . '/' . ltrim($path, '/');
    }
}

