<?php

namespace App\Http\Controllers\Api;

use App\Enums\MediaType;
use App\Http\Controllers\Controller;
use App\Http\Resources\MediaItemResource;
use App\Models\MediaItem;
use App\Models\SearchQuery;
use App\Services\MediaSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Throwable;

class MediaController extends Controller
{
    public function __construct(
        protected MediaSearchService $searchService
    ) {}

    /**
     * 统一搜索接口
     * GET /api/media/search?type=movie&keyword=周星驰&refresh=0
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::enum(MediaType::class)],
            'keyword' => ['required', 'string', 'min:1', 'max:100'],
            'refresh' => ['nullable', 'boolean'],
        ], [
            'type.required' => '请指定媒体类型 (type: movie, tv, game, music)',
            'type.Illuminate\Validation\Rules\Enum' => '媒体类型仅支持: movie, tv, game, music',
            'keyword.required' => '请输入搜索关键词 (keyword)',
        ]);

        $type = MediaType::from($validated['type']);
        $keyword = trim($validated['keyword']);
        $forceRefresh = $request->boolean('refresh', false);

        $result = $this->searchService->search($type, $keyword, limit: 20, forceRefresh: $forceRefresh);
        $searchQuery = $result['search_query'];

        $meta = [
            'type' => $type->value,
            'type_label' => $type->label(),
            'keyword' => $keyword,
            'provider' => $result['provider'],
            'hit_source' => $result['hit_source'], // remote_api | cache | database | database_fallback
            'count' => $result['items']->count(),
            'search_count' => $searchQuery?->search_count,
            'api_request_count' => $searchQuery?->api_request_count,
            'last_duration_ms' => $searchQuery?->last_duration_ms,
            'last_api_requested_at' => $searchQuery?->last_api_requested_at?->toDateTimeString(),
        ];

        // 调试模式下如果远程 API 发生网络报错触发了降级，在 meta 中附上错误原因便于排查
        if (config('app.debug') && ! empty($result['error'])) {
            $meta['api_error'] = $result['error'];
        }

        return response()->json([
            'code' => 0,
            'message' => 'ok',
            'meta' => $meta,
            'data' => MediaItemResource::collection($result['items']),
        ]);
    }

    /**
     * 查询搜索词统计与第三方 API 请求日志列表
     * GET /api/media/queries?type=movie&zero_only=1
     */
    public function queries(Request $request): JsonResponse
    {
        $query = SearchQuery::query();

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->boolean('zero_only')) {
            $query->where('result_count', 0);
        }

        $list = $query->orderByDesc('search_count')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return response()->json([
            'code' => 0,
            'message' => 'ok',
            'data' => $list,
        ]);
    }

    /**
     * 手动录入/测试写入媒体条目（同时可用于小程序端用户自定义添加找不到的作品）
     * POST /api/media
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::enum(MediaType::class)],
            'provider' => ['nullable', 'string', 'max:30'],
            'external_id' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'original_title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'release_year' => ['nullable', 'string', 'max:10'],
            'cover_url' => ['nullable', 'url', 'max:1000'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'extra_data' => ['nullable', 'array'],
        ]);

        $provider = $validated['provider'] ?? 'custom';
        $externalId = $validated['external_id'] ?? md5($validated['type'] . ':' . $validated['title']);

        $item = MediaItem::query()->updateOrCreate(
            [
                'type' => $validated['type'],
                'provider' => $provider,
                'external_id' => $externalId,
            ],
            [
                'title' => $validated['title'],
                'original_title' => $validated['original_title'] ?? null,
                'subtitle' => $validated['subtitle'] ?? null,
                'release_year' => $validated['release_year'] ?? null,
                'cover_url' => $validated['cover_url'] ?? null,
                'rating' => $validated['rating'] ?? null,
                'extra_data' => $validated['extra_data'] ?? [],
            ]
        );

        return response()->json([
            'code' => 0,
            'message' => 'saved',
            'data' => new MediaItemResource($item),
        ], 201);
    }

    /**
     * 图片代理接口（解决前端 Canvas 绘制海报跨域污染与第三方防盗链问题）
     * GET /api/media/image-proxy?url=https://...
     */
    public function imageProxy(Request $request): Response
    {
        $url = trim((string) $request->query('url', ''));

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            abort(400, 'Invalid image url');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            abort(400, 'Unsupported url scheme');
        }

        try {
            $client = Http::timeout(10)
                ->withUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36')
                ->withOptions(['allow_redirects' => true]);

            $proxy = config('services.third_party_proxy');
            if (! empty($proxy)) {
                $client->withOptions(['proxy' => $proxy]);
            }

            // 针对豆瓣图床补充 Referer 防盗链头
            if (str_contains($url, 'doubanio.com')) {
                $client->withHeaders(['Referer' => 'https://movie.douban.com/']);
            }

            $response = $client->get($url);

            if (! $response->successful()) {
                abort(404, 'Image fetch failed');
            }

            $contentType = $response->header('Content-Type') ?: 'image/jpeg';

            return response($response->body(), 200, [
                'Content-Type' => $contentType,
                'Cache-Control' => 'public, max-age=604800',
                'Access-Control-Allow-Origin' => '*',
            ]);
        } catch (Throwable) {
            abort(404, 'Image unavailable');
        }
    }
}
