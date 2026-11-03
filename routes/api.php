<?php

use App\Http\Controllers\Api\MediaController;
use Illuminate\Support\Facades\Route;

Route::prefix('media')->group(function () {
    // 统一搜索接口: GET /api/media/search?type=movie&keyword=星际穿越
    Route::get('/search', [MediaController::class, 'search']);

    // 搜索词统计与第三方API调用日志: GET /api/media/queries?zero_only=1
    Route::get('/queries', [MediaController::class, 'queries']);

    // 手动创建/测试入库接口: POST /api/media
    Route::post('/', [MediaController::class, 'store']);
});
