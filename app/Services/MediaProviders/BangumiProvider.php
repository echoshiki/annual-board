<?php

namespace App\Services\MediaProviders;

use App\Contracts\MediaProviderInterface;
use App\Enums\MediaType;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BangumiProvider implements MediaProviderInterface
{
    public function providerName(): string
    {
        return 'bangumi';
    }

    /**
     * 调用 Bangumi API v0 搜索游戏条目 (Subject Type = 4)
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(MediaType $type, string $keyword, int $limit = 20): array
    {
        if ($type !== MediaType::GAME) {
            throw new RuntimeException("Bangumi 驱动当前仅用于游戏类型，收到: {$type->value}");
        }

        // Bangumi v0 要求 limit 与 offset 放在 URL Query 参数中
        $endpoint = sprintf('/v0/search/subjects?limit=%d&offset=0', $limit);

        $response = $this->httpClient()->post($endpoint, [
            'keyword' => $keyword,
            'sort' => 'match',
            'filter' => [
                'type' => [4], // 4 = 游戏 (Game)
                'nsfw' => false,
            ],
        ]);

        $response->throw();

        $results = $response->json('data', []);
        if (! is_array($results)) {
            return [];
        }

        $items = [];
        foreach (array_slice($results, 0, $limit) as $row) {
            if (! is_array($row) || empty($row['id'])) {
                continue;
            }

            $items[] = $this->normalizeGame($row);
        }

        return $items;
    }

    /**
     * 构建 Bangumi HTTP 客户端（Bangumi 官方强制要求非空自定义 User-Agent）
     */
    protected function httpClient(): PendingRequest
    {
        $baseUrl = rtrim((string) config('services.bangumi.base_url', 'https://api.bgm.tv'), '/');
        $timeout = (int) config('services.bangumi.timeout', 10);
        $userAgent = (string) config('services.bangumi.user_agent', 'shiki/annual-board (https://github.com/annual-board)');
        $token = config('services.bangumi.token');

        $client = Http::baseUrl($baseUrl)
            ->timeout($timeout)
            ->withUserAgent($userAgent)
            ->acceptJson()
            ->asJson();

        if (! empty($token)) {
            $client->withToken($token);
        }

        return $client;
    }

    /**
     * 标准化 Bangumi 游戏条目数据
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalizeGame(array $row): array
    {
        $nameCn = trim((string) ($row['name_cn'] ?? ''));
        $originalName = trim((string) ($row['name'] ?? ''));
        $title = $nameCn !== '' ? $nameCn : ($originalName !== '' ? $originalName : '未知游戏');

        $releaseDate = (string) ($row['date'] ?? '');
        $releaseYear = strlen($releaseDate) >= 4 ? substr($releaseDate, 0, 4) : null;

        // 提取前 3 个高赞标签（通常包含平台如 Switch/PC、开发商如 任天堂/FromSoftware、类型如 RPG）作为副标题
        $topTags = $this->extractTopTags($row['tags'] ?? [], 3);
        $summary = trim((string) ($row['summary'] ?? ''));

        $subtitle = null;
        if (! empty($topTags)) {
            $subtitle = implode(' · ', $topTags);
        } elseif ($originalName !== '' && $originalName !== $title) {
            $subtitle = $originalName;
        } elseif ($summary !== '') {
            $subtitle = mb_strimwidth($summary, 0, 50, '...');
        }

        // 优先取 large 高清竖版封面，其次取 common 或 image
        $coverUrl = data_get($row, 'images.large')
            ?: data_get($row, 'images.common')
            ?: ($row['image'] ?? null);

        $score = data_get($row, 'rating.score') ?? ($row['score'] ?? null);

        return [
            'type' => MediaType::GAME->value,
            'provider' => $this->providerName(),
            'external_id' => (string) $row['id'],
            'title' => $title,
            'original_title' => $originalName !== '' ? $originalName : null,
            'subtitle' => $subtitle,
            'release_year' => $releaseYear,
            'cover_url' => $coverUrl,
            'rating' => ($score !== null && (float) $score > 0) ? round((float) $score, 1) : null,
            'extra_data' => [
                'release_date' => $releaseDate ?: null,
                'summary' => $summary ?: null,
                'tags' => $this->extractTopTags($row['tags'] ?? [], 8),
                'rank' => data_get($row, 'rating.rank') ?? ($row['rank'] ?? null),
                'rating_total' => data_get($row, 'rating.total'),
                'images' => $row['images'] ?? null,
            ],
        ];
    }

    /**
     * 提取热门标签名称列表
     *
     * @param  array<int, mixed>  $tags
     * @return array<int, string>
     */
    protected function extractTopTags(array $tags, int $limit = 3): array
    {
        $names = [];
        foreach ($tags as $tag) {
            if (is_array($tag) && ! empty($tag['name'])) {
                $names[] = trim((string) $tag['name']);
                if (count($names) >= $limit) {
                    break;
                }
            }
        }

        return $names;
    }
}

