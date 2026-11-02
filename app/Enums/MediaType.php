<?php

namespace App\Enums;

enum MediaType: string
{
    case MOVIE = 'movie';
    case TV = 'tv';
    case GAME = 'game';
    case MUSIC = 'music';

    public function label(): string
    {
        return match ($this) {
            self::MOVIE => '电影',
            self::TV => '剧集',
            self::GAME => '游戏',
            self::MUSIC => '音乐',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

