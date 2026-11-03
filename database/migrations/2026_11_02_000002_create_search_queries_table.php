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
        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->comment('媒体类型: movie, tv, game, music');
            $table->string('keyword', 100)->comment('搜索关键词(标准化小写)');
            $table->string('provider', 30)->nullable()->comment('第三方数据源: tmdb, bangumi, itunes');
            $table->unsignedBigInteger('search_count')->default(0)->comment('累计搜索总次数(含命中缓存)');
            $table->unsignedBigInteger('api_request_count')->default(0)->comment('实际请求第三方API次数');
            $table->unsignedInteger('result_count')->default(0)->comment('最近一次搜索返回结果数(0代表未搜到)');
            $table->string('last_hit_source', 30)->nullable()->comment('最近一次命中源: remote_api, cache, database 等');
            $table->string('last_status', 20)->default('success')->comment('最近一次API状态: success, empty, error');
            $table->unsignedInteger('last_duration_ms')->nullable()->comment('最近一次请求第三方API耗时(毫秒)');
            $table->text('last_error')->nullable()->comment('最近一次第三方API异常信息');
            $table->timestamp('last_api_requested_at')->nullable()->comment('最近一次请求第三方API的时间');
            $table->timestamps();

            // 同一类型+同一关键词只占一行
            $table->unique(['type', 'keyword'], 'search_queries_type_keyword_unique');
            // 便于快速筛选零结果词与热搜词
            $table->index(['type', 'result_count'], 'search_queries_type_result_idx');
            $table->index(['type', 'search_count'], 'search_queries_type_hot_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};

