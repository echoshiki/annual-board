<?php

namespace App\Services\MediaProviders;

use App\Contracts\MediaProviderInterface;
use App\Enums\MediaType;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MusicBrainzProvider implements MediaProviderInterface
{
    public function providerName(): string
    {
        return 'musicbrainz';
    }

    /**
     * 调用 MusicBrainz 搜索音乐录音 (Recording)
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(MediaType $type, string $keyword, int $limit = 20): array
    {
        if ($type !== MediaType::MUSIC) {
            throw new RuntimeException("MusicBrainz 驱动不支持该媒体类型: {$type->value}");
        }

        $response = $this->httpClient()->get('/recording', [
            'query' => $keyword,
            'limit' => $limit,
            'fmt' => 'json',
        ]);

        $response->throw();

        $recordings = $response->json('recordings', []);
        if (! is_array($recordings)) {
            return [];
        }

        $items = [];
        foreach (array_slice($recordings, 0, $limit) as $row) {
            if (! is_array($row) || empty($row['id'])) {
                continue;
            }

            $items[] = $this->normalizeRecording($row);
        }

        return $items;
    }

    /**
     * 构建 MusicBrainz HTTP 客户端（MusicBrainz 强制要求携带合规 User-Agent）
     */
    protected function httpClient(): PendingRequest
    {
        $baseUrl = rtrim((string) config('services.musicbrainz.base_url', 'https://musicbrainz.org/ws/2'), '/');
        $timeout = (int) config('services.musicbrainz.timeout', 10);
        $userAgent = (string) config('services.musicbrainz.user_agent', 'AnnualBoard/1.0 (contact@example.com)');

        return Http::baseUrl($baseUrl)
            ->timeout($timeout)
            ->withUserAgent($userAgent)
            ->acceptJson();
    }

    /**
     * 标准化单曲/录音数据
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalizeRecording(array $row): array
    {
        $title = trim((string) ($row['title'] ?? '未知曲目'));
        $artistName = $this->extractArtistName($row['artist-credit'] ?? []);
        $bestRelease = $this->pickBestRelease($row['releases'] ?? []);

        $albumTitle = trim((string) ($bestRelease['title'] ?? ''));
        $releaseId = $bestRelease['id'] ?? null;
        $releaseGroupId = data_get($bestRelease, 'release-group.id');

        // 发行日期优先取 recording 的 first-release-date，其次取专辑的 date
        $releaseDate = (string) ($row['first-release-date'] ?? ($bestRelease['date'] ?? ''));
        $releaseYear = strlen($releaseDate) >= 4 ? substr($releaseDate, 0, 4) : null;

        // 拼接副标题：歌手 · 专辑名
        $subtitleParts = array_filter([$artistName, $albumTitle], fn ($v) => $v !== null && $v !== '');
        $subtitle = ! empty($subtitleParts) ? implode(' · ', $subtitleParts) : null;

        // 封面图地址（通过官方 Cover Art Archive 获取 500px 封面）
        // release-group 的封面命中率显著高于单个发行版 release
        $coverBaseUrl = rtrim((string) config('services.musicbrainz.cover_base_url', 'https://coverartarchive.org'), '/');
        $coverUrl = null;
        if (! empty($releaseGroupId)) {
            $coverUrl = "{$coverBaseUrl}/release-group/{$releaseGroupId}/front-500";
        } elseif (! empty($releaseId)) {
            $coverUrl = "{$coverBaseUrl}/release/{$releaseId}/front-500";
        }

        return [
            'type' => MediaType::MUSIC->value,
            'provider' => $this->providerName(),
            'external_id' => (string) $row['id'],
            'title' => $title,
            'original_title' => null,
            'subtitle' => $subtitle,
            'release_year' => $releaseYear,
            'cover_url' => $coverUrl,
            'rating' => null,
            'extra_data' => [
                'artist' => $artistName,
                'album' => $albumTitle ?: null,
                'release_date' => $releaseDate ?: null,
                'duration_ms' => $row['length'] ?? null,
                'release_id' => $releaseId,
                'release_group_id' => $releaseGroupId,
                'release_cover_url' => ! empty($releaseId) ? "{$coverBaseUrl}/release/{$releaseId}/front-500" : null,
                'match_score' => $row['score'] ?? null,
            ],
        ];
    }

    /**
     * 解析歌手名称列表（拼接多歌手及连接符）
     *
     * @param  array<int, mixed>  $artistCredits
     */
    protected function extractArtistName(array $artistCredits): ?string
    {
        if (empty($artistCredits)) {
            return null;
        }

        $result = '';
        foreach ($artistCredits as $credit) {
            if (is_string($credit)) {
                $result .= $credit;
                continue;
            }
            if (is_array($credit)) {
                $name = $credit['name'] ?? data_get($credit, 'artist.name', '');
                $joinPhrase = $credit['joinphrase'] ?? '';
                $result .= $name . $joinPhrase;
            }
        }

        $result = trim($result);

        return $result !== '' ? $result : null;
    }

    /**
     * 从录音关联的多个发行专辑中挑选最合适的一个（优先 Official 且含 release-group 的专辑）
     *
     * @param  array<int, mixed>  $releases
     * @return array<string, mixed>|null
     */
    protected function pickBestRelease(array $releases): ?array
    {
        if (empty($releases)) {
            return null;
        }

        foreach ($releases as $release) {
            if (! is_array($release)) {
                continue;
            }
            $status = strtolower((string) ($release['status'] ?? ''));
            if ($status === 'official' && ! empty(data_get($release, 'release-group.id'))) {
                return $release;
            }
        }

        return is_array($releases[0]) ? $releases[0] : null;
    }
}

