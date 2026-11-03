<?php

namespace App\Contracts;

use App\Enums\MediaType;

interface MediaProviderInterface
{
    /**
     * 数据源标识名称（如 tmdb, bangumi, itunes）
     */
    public function providerName(): string;

    /**
     * 根据媒体类型与关键词搜索，返回标准化数组列表
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(MediaType $type, string $keyword, int $limit = 20): array;
}

