<?php

namespace App\Services\MediaProviders;

use App\Contracts\MediaProviderInterface;
use App\Enums\MediaType;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SteamProvider implements MediaProviderInterface
{
    public function providerName(): string
    {
        return 'steam';
    }

    /**
     * 调用 Steam 官方商店搜索接口（原生支持简体中文 l=schinese&cc=CN，免 Key）
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(MediaType $type, string $keyword, int $limit = 20): array
    {
        if ($type !== MediaType::GAME) {
            throw new RuntimeException("Steam 驱动仅支持游戏类型，收到: {$type->value}");
        }

        $response = $this->httpClient()->get('/api/storesearch/', [
            'term' => $keyword,
            'l' => 'schinese',
            'cc' => 'CN',
        ]);

        $response->throw();

        $items = $response->json('items', []);
        if (! is_array($items)) {
            return [];
        }

        $results = [];
        foreach (array_slice($items, 0, $limit) as $row) {
            if (! is_array($row) || empty($row['id'])) {
                continue;
            }

            $results[] = $this->normalizeGame($row);
        }

        return $results;
    }

    protected function httpClient(): PendingRequest
    {
        $baseUrl = rtrim((string) config('services.steam.base_url', 'https://store.steampowered.com'), '/');
        $timeout = (int) config('services.steam.timeout', 10);

        $client = Http::baseUrl($baseUrl)
            ->timeout($timeout)
            ->acceptJson();

        $proxy = config('services.third_party_proxy');
        if (! empty($proxy)) {
            $client->withOptions(['proxy' => $proxy]);
        }

        return $client;
    }

    /**
     * 标准化 Steam 游戏数据
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalizeGame(array $row): array
    {
        $appId = (string) $row['id'];
        $title = trim((string) ($row['name'] ?? '未知游戏'));

        // 提取支持平台
        $platforms = [];
        if (! empty(data_get($row, 'platforms.windows'))) {
            $platforms[] = 'Windows';
        }
        if (! empty(data_get($row, 'platforms.mac'))) {
            $platforms[] = 'Mac';
        }
        if (! empty(data_get($row, 'platforms.linux'))) {
            $platforms[] = 'Linux';
        }
        $subtitle = ! empty($platforms) ? 'Steam · ' . implode(' / ', $platforms) : 'Steam';

        // 提取 Metacritic 评分（0-100 转换为 10 分制）
        $rating = null;
        if (! empty($row['metascore']) && is_numeric($row['metascore'])) {
            $score = (float) $row['metascore'];
            if ($score > 0) {
                $rating = round($score / 10, 1);
            }
        }

        // Steam 官方 600x900 竖版游戏库海报地址
        $cdnBase = "https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/{$appId}";
        $verticalCoverUrl = "{$cdnBase}/library_600x900.jpg";

        return [
            'type' => MediaType::GAME->value,
            'provider' => $this->providerName(),
            'external_id' => $appId,
            'title' => $title,
            'original_title' => null,
            'subtitle' => $subtitle,
            'release_year' => null,
            'cover_url' => $verticalCoverUrl,
            'rating' => $rating,
            'extra_data' => [
                'steam_appid' => (int) $appId,
                'platforms' => $platforms,
                'metascore' => $row['metascore'] ?? null,
                'header_image' => "{$cdnBase}/header.jpg",
                'tiny_image' => $row['tiny_image'] ?? null,
                'price_cny' => isset($row['price']['final']) ? ($row['price']['final'] / 100) : null,
                'store_url' => "https://store.steampowered.com/app/{$appId}/",
            ],
        ];
    }
}

