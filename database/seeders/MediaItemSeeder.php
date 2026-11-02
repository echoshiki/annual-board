<?php

namespace Database\Seeders;

use App\Enums\MediaType;
use App\Models\MediaItem;
use Illuminate\Database\Seeder;

class MediaItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $samples = [
            [
                'type' => MediaType::GAME->value,
                'provider' => 'bangumi',
                'external_id' => '325585',
                'title' => '塞尔达传说 王国之泪',
                'original_title' => 'ゼルダの伝説 ティアーズ オブ ザ キングダム',
                'subtitle' => 'Nintendo',
                'release_year' => '2023',
                'cover_url' => 'https://lain.bgm.tv/pic/cover/l/62/51/325585_22528.jpg',
                'rating' => 9.2,
                'extra_data' => ['platform' => 'Nintendo Switch'],
            ],
            [
                'type' => MediaType::MOVIE->value,
                'provider' => 'tmdb',
                'external_id' => '157336',
                'title' => '星际穿越',
                'original_title' => 'Interstellar',
                'subtitle' => '克里斯托弗·诺兰',
                'release_year' => '2014',
                'cover_url' => 'https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg',
                'rating' => 8.4,
                'extra_data' => ['genres' => ['科幻', '冒险', '剧情']],
            ],
            [
                'type' => MediaType::TV->value,
                'provider' => 'tmdb',
                'external_id' => '1396',
                'title' => '绝命毒师',
                'original_title' => 'Breaking Bad',
                'subtitle' => 'Vince Gilligan',
                'release_year' => '2008',
                'cover_url' => 'https://image.tmdb.org/t/p/w500/ztkUQFLlC19CCMYHW9o1zWhJRNq.jpg',
                'rating' => 8.9,
                'extra_data' => ['seasons' => 5],
            ],
            [
                'type' => MediaType::MUSIC->value,
                'provider' => 'itunes',
                'external_id' => '536180459',
                'title' => '晴天',
                'original_title' => '晴天',
                'subtitle' => '周杰伦 · 叶惠美',
                'release_year' => '2003',
                'cover_url' => 'https://is1-ssl.mzstatic.com/image/thumb/Music115/v4/3c/4b/98/3c4b9868-a38a-7459-6766-e6f0c1bd5628/mzi.bkozrgrx.jpg/600x600bb.jpg',
                'rating' => null,
                'extra_data' => ['album' => '叶惠美'],
            ],
        ];

        foreach ($samples as $sample) {
            MediaItem::query()->updateOrCreate(
                [
                    'type' => $sample['type'],
                    'provider' => $sample['provider'],
                    'external_id' => $sample['external_id'],
                ],
                $sample
            );
        }
    }
}

