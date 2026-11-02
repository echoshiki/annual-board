<?php

use App\Http\Controllers\Api\MediaController;
use Illuminate\Support\Facades\Route;

Route::prefix('media')->group(function () {
    // 统一搜索接口: GET /api/media/search?type=game&keyword=塞尔达
    Route::get('/search', [MediaController::class, 'search']);

    // 手动创建/测试入库接口: POST /api/media
    Route::post('/', [MediaController::class, 'store']);
});

