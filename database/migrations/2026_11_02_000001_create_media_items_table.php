<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->comment('媒体类型: movie, tv, game, music');
            $table->string('provider', 30)->comment('数据源: tmdb, bangumi, itunes 等');
            $table->string('external_id', 100)->comment('第三方平台唯一ID');
            $table->string('title')->comment('主标题/中文名');
            $table->string('original_title')->nullable()->comment('原名/外文名');
            $table->string('subtitle')->nullable()->comment('副标题(导演/演员/歌手/开发商)');
            $table->string('release_year', 10)->nullable()->comment('发行/上映年份');
            $table->text('cover_url')->nullable()->comment('封面/海报原图地址');
            $table->decimal('rating', 3, 1)->nullable()->comment('评分(0.0-10.0)');
            $table->json('extra_data')->nullable()->comment('扩展元数据(JSON)');
            $table->timestamps();

            // 联合唯一索引，防止同一第三方数据源的同一条目重复入库
            $table->unique(['type', 'provider', 'external_id'], 'media_items_unique_source');
            // 复合索引，加速按类型+标题的本地模糊搜索
            $table->index(['type', 'title'], 'media_items_type_title_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_items');
    }
};

