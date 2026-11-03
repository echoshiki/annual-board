<?php

use App\Http\Controllers\Api\MediaController;
use Illuminate\Support\Facades\Route;

Route::prefix('media')->group(function () {
    // 统一搜索接口: GET /api/media/search?type=movie&keyword=星际穿越
    Route::get('/search', [MediaController::class, 'search']);

    // 搜索词统计与第三方API调用日志: GET /api/media/queries?zero_only=1
    Route::get('/queries', [MediaController::class, 'queries']);

    // 图片跨域与防盗链代理接口: GET /api/media/image-proxy?url=...
    Route::get('/image-proxy', [MediaController::class, 'imageProxy']);

    // 手动创建/测试入库接口: POST /api/media
    Route::post('/', [MediaController::class, 'store']);
});
