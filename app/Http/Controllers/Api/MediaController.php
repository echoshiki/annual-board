<?php

namespace App\Http\Controllers\Api;

use App\Enums\MediaType;
use App\Http\Controllers\Controller;
use App\Http\Resources\MediaItemResource;
use App\Models\MediaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MediaController extends Controller
{
    /**
     * 统一搜索接口
     * GET /api/media/search?type=game&keyword=塞尔达
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::enum(MediaType::class)],
            'keyword' => ['required', 'string', 'min:1', 'max:100'],
        ], [
            'type.required' => '请指定媒体类型 (type: movie, tv, game, music)',
            'type.Illuminate\Validation\Rules\Enum' => '媒体类型仅支持: movie, tv, game, music',
            'keyword.required' => '请输入搜索关键词 (keyword)',
        ]);

        $type = MediaType::from($validated['type']);
        $keyword = trim($validated['keyword']);

        // 第一步：目前先从本地数据库检索（第二步将接入 MediaSearchService 联动第三方 API 与 Redis）
        $items = MediaItem::query()
            ->searchLocal($type, $keyword)
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        return response()->json([
            'code' => 0,
            'message' => 'ok',
            'meta' => [
                'type' => $type->value,
                'type_label' => $type->label(),
                'keyword' => $keyword,
                'hit_source' => 'database',
                'count' => $items->count(),
            ],
            'data' => MediaItemResource::collection($items),
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
}

