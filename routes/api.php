<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\RedisTestController;
use Illuminate\Support\Facades\Redis;

// 產品 API 路由
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::put('/{id}', [ProductController::class, 'update']);
});

// 快取狀態監控 API
Route::get('/cache-stats', function (App\Services\CacheService $cacheService) {
    return response()->json($cacheService->getCacheStats());
});

// 在 routes/api.php 中添加
// routes/api.php - 修正 cache-test 路由
Route::get('/cache-test', function () {
    $cacheKey = 'api:test';
    
    // 查看是否存在快取 (不要每次都清除!)
    if (Cache::has($cacheKey)) {
        $source = 'cache';
        $data = Cache::get($cacheKey);
    } else {
        $source = 'original';
        $data = [
            'message' => 'This is cached data',
            'time' => time(),
            'random' => rand(1000, 9999)  // 添加隨機數，確認是否從快取讀取
        ];
        
        // 儲存到快取，60秒過期
        Cache::put($cacheKey, $data, 60);
    }
    
    return [
        'data' => $data,
        'source' => $source,
        'cache_key' => $cacheKey
    ];
});

// 新增清除快取的端點，方便測試
Route::get('/clear-cache-test', function () {
    $cacheKey = 'api:test';
    Cache::forget($cacheKey);
    return ['message' => '快取已清除', 'key' => $cacheKey];
});

// routes/api.php - 添加新路由
Route::get('/test-product-cache/{id}', function ($id) {
    return [
        'id' => $id,
        'name' => '測試產品 ' . $id,
        'price' => $id * 100,
        'timestamp' => time(),
        'random' => rand(1000, 9999)
    ];
});

// routes/api.php - 添加 Redis 測試端點
Route::get('/redis-test', function () {
    try {
        $redis = Redis::connection()->client();

        // 寫入測試鍵
        $testKey = 'direct_test_' . time();
        $redis->set($testKey, 'test_value');
        
        // 獲取所有鍵
        $allKeys = $redis->keys('*');
        $prefix = config('cache.prefix', 'laravel_cache');
        $apiKeys = $redis->keys("{$prefix}:api:*");
        
        return [
            'success' => true,
            'redis_ping' => $redis->ping() === true || $redis->ping() === '+PONG',
            'test_key' => $testKey,
            'test_value' => $redis->get($testKey),
            'all_keys_count' => count($allKeys),
            'all_keys_sample' => array_slice($allKeys, 0, 10),
            'api_keys_count' => count($apiKeys),
            'api_keys_sample' => array_slice($apiKeys, 0, 10),
            'cache_prefix' => $prefix,
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => explode("\n", $e->getTraceAsString())
        ];
    }
});

// 新增 Redis 詳細診斷路由
Route::get('/detailed-redis-test', [RedisTestController::class, 'testConnection']);