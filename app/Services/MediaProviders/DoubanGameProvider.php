<?php

namespace App\Services\MediaProviders;

use App\Contracts\MediaProviderInterface;
use App\Enums\MediaType;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DoubanGameProvider implements MediaProviderInterface
{
    public function providerName(): string
    {
        return 'douban';
    }

    /**
     * 调用豆瓣游戏搜索接口 (cat=3114 代表豆瓣游戏分类)
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(MediaType $type, string $keyword, int $limit = 20): array
    {
        if ($type !== MediaType::GAME) {
            throw new RuntimeException("DoubanGame 驱动当前仅用于游戏类型，收到: {$type->value}");
        }

        $response = $this->httpClient($keyword)->get('/j/search', [
            'q' => $keyword,
            'cat' => '3114', // 3114 = 豆瓣游戏 (Game)
            'start' => 0,
        ]);

        $response->throw();

        $items = $response->json('items', []);
        if (! is_array($items)) {
            return [];
        }

        $results = [];
        foreach ($items as $rawItem) {
            $parsed = is_string($rawItem)
                ? $this->parseHtmlSnippet($rawItem)
                : (is_array($rawItem) ? $this->parseJsonObject($rawItem) : null);

            if ($parsed !== null) {
                $results[] = $parsed;
                if (count($results) >= $limit) {
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * 构建带浏览器特征 Header 的豆瓣请求客户端
     */
    protected function httpClient(string $keyword): PendingRequest
    {
        $baseUrl = rtrim((string) config('services.douban.base_url', 'https://www.douban.com'), '/');
        $timeout = (int) config('services.douban.timeout', 10);
        $userAgent = (string) config(
            'services.douban.user_agent',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
        );

        // 生成 11 位随机 bid Cookie，绕过豆瓣基础匿名请求 403 拦截
        $randomBid = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 11);

        return Http::baseUrl($baseUrl)
            ->timeout($timeout)
            ->withUserAgent($userAgent)
            ->withHeaders([
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
                'X-Requested-With' => 'XMLHttpRequest',
                'Cookie' => "bid={$randomBid};",
                'Sec-Fetch-Dest' => 'empty',
                'Sec-Fetch-Mode' => 'cors',
                'Sec-Fetch-Site' => 'same-origin',
                'Referer' => $baseUrl . '/search?cat=3114&q=' . urlencode($keyword),
            ]);
    }

    /**
     * 解析豆瓣 /j/search 返回的单条 HTML 片段
     *
     * @return array<string, mixed>|null
     */
    protected function parseHtmlSnippet(string $html): ?array
    {
        // 1. 提取标题
        if (! preg_match('/<div class="title">.*?<a[^>]*>(.*?)<\/a>/si', $html, $titleMatch)) {
            return null;
        }
        $title = trim(html_entity_decode(strip_tags($titleMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($title === '') {
            return null;
        }

        // 2. 提取豆瓣游戏条目 ID (sid: 123456 或 /game/123456/ 或 /subject/123456/)
        $externalId = null;
        if (preg_match('/sid:\s*[\'"]?(\d+)[\'"]?/i', $html, $idMatch)) {
            $externalId = $idMatch[1];
        } elseif (preg_match('/\/(?:game|subject|thing)\/(\d+)/i', $html, $idMatch)) {
            $externalId = $idMatch[1];
        } else {
            $externalId = substr(md5($title), 0, 16);
        }

        // 3. 提取封面图 URL
        $coverUrl = null;
        $thumbUrl = null;
        if (preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $html, $imgMatch)) {
            $thumbUrl = trim($imgMatch[1]);
            // 豆瓣缩略图转高清图（将 small/spic 转换为 medium/lpic）
            $coverUrl = $this->upgradeDoubanImageUrl($thumbUrl);
        }

        // 4. 提取评分
        $rating = null;
        if (preg_match('/<span class="rating_nums">([0-9.]+)<\/span>/i', $html, $rateMatch)) {
            $ratingVal = (float) $rateMatch[1];
            if ($ratingVal > 0) {
                $rating = round($ratingVal, 1);
            }
        }

        // 5. 提取平台、类型与发行日期 (subject-cast)
        // 示例: "PC / PS5 / 动作冒险 / 2024-08-20"
        $subjectCast = null;
        $releaseYear = null;
        $releaseDate = null;
        if (preg_match('/<span class="subject-cast">(.*?)<\/span>/si', $html, $castMatch)) {
            $subjectCast = trim(html_entity_decode(strip_tags($castMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if (preg_match('/\b(19\d{2}|20\d{2})(?:-\d{1,2}(?:-\d{1,2})?)?\b/', $subjectCast, $dateMatch)) {
                $releaseDate = $dateMatch[0];
                $releaseYear = $dateMatch[1];
            }
        }

        // 6. 提取简介摘要
        $summary = null;
        if (preg_match('/<p>(.*?)<\/p>/si', $html, $descMatch)) {
            $summary = trim(html_entity_decode(strip_tags($descMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        $subtitle = $subjectCast ?: ($summary ? mb_strimwidth($summary, 0, 50, '...') : null);

        return [
            'type' => MediaType::GAME->value,
            'provider' => $this->providerName(),
            'external_id' => (string) $externalId,
            'title' => $title,
            'original_title' => null,
            'subtitle' => $subtitle,
            'release_year' => $releaseYear,
            'cover_url' => $coverUrl,
            'rating' => $rating,
            'extra_data' => [
                'release_date' => $releaseDate,
                'subject_cast' => $subjectCast,
                'summary' => $summary,
                'thumb_url' => $thumbUrl,
                'douban_url' => "https://www.douban.com/game/{$externalId}/",
            ],
        ];
    }

    /**
     * 兼容结构化 JSON 对象的解析
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    protected function parseJsonObject(array $row): ?array
    {
        $title = trim((string) ($row['title'] ?? ''));
        if ($title === '') {
            return null;
        }

        $externalId = (string) ($row['id'] ?? substr(md5($title), 0, 16));
        $abstract = trim((string) ($row['abstract'] ?? $row['sub_title'] ?? ''));
        $releaseYear = $row['year'] ?? null;

        if (! $releaseYear && preg_match('/\b(19\d{2}|20\d{2})\b/', $abstract, $m)) {
            $releaseYear = $m[1];
        }

        $thumbUrl = $row['pic'] ?? $row['img'] ?? null;

        return [
            'type' => MediaType::GAME->value,
            'provider' => $this->providerName(),
            'external_id' => $externalId,
            'title' => $title,
            'original_title' => $row['sub_title'] ?? null,
            'subtitle' => $abstract ?: null,
            'release_year' => $releaseYear ? (string) $releaseYear : null,
            'cover_url' => $thumbUrl ? $this->upgradeDoubanImageUrl((string) $thumbUrl) : null,
            'rating' => isset($row['rate']) && (float) $row['rate'] > 0 ? round((float) $row['rate'], 1) : null,
            'extra_data' => [
                'thumb_url' => $thumbUrl,
                'url' => $row['url'] ?? null,
            ],
        ];
    }

    /**
     * 将豆瓣搜索列表的小尺寸缩略图升级为更清晰的竖版海报图
     */
    protected function upgradeDoubanImageUrl(string $url): string
    {
        return str_replace(
            ['/view/game/small/', '/view/thing_review/small/', '/spic/', '/icon/'],
            ['/view/game/medium/', '/view/thing_review/large/', '/lpic/', '/lpic/'],
            $url
        );
    }
}

