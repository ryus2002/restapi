<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class CacheService
{
    /**
     * 根據標籤批次清除快取
     */
    public function clearCacheByTag(string $tag): bool
    {
        try {
            $redis = Redis::connection()->client();
            $prefix = config('cache.prefix', 'laravel_cache');
            
            // 尋找包含此標籤的所有鍵
            $pattern = "*:{$tag}*";
            $keys = $redis->keys($prefix . ':' . $pattern);
            
            // 移除 Laravel 快取前綴以便使用 Cache::forget
            foreach ($keys as $key) {
                $cacheKey = str_replace($prefix . ':', '', $key);
                Cache::forget($cacheKey);
            }
            
            return true;
        } catch (\Exception $e) {
            \Log::error('清除快取標籤失敗: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 選擇性更新快取
     */
    public function updateCache(string $key, array $data, int $expiresAt = 600): bool
    {
        if (Cache::has($key)) {
            $cachedData = Cache::get($key);
            
            // 合併新舊數據，只更新變更部分
            $updatedData = array_merge($cachedData, $data);
            
            Cache::put($key, $updatedData, $expiresAt);
            return true;
        }
        
        return false;
    }
    
    /**
     * 後台預熱快取（預先更新即將過期的資料）
     */
    public function prewarmExpiringSoon(int $threshold = 60): void
    {
        // 此處邏輯需與您的應用程式結合
        // 實際生產環境應使用隊列任務來執行
    }
        
    /**
     * 獲取快取統計資訊
     */
    public function getCacheStats(): array
    {
        try {
            // 使用 Redis facade 獲取連接
            $redis = Redis::connection()->client();
            
            // 嘗試執行一個簡單的 Redis 指令來確認連接是否正常
            $pingResult = $redis->ping();
            
            $info = $redis->info();
            
            // 獲取所有鍵以確認 Redis 中有數據
            $allKeys = $redis->keys('*');
            
            // Redis info 返回一維陣列，直接存取
            $hits = (int)($info['keyspace_hits'] ?? 0);
            $misses = (int)($info['keyspace_misses'] ?? 0);
            $totalOps = $hits + $misses;
            
            // 獲取所有以 api: 開頭的快取鍵（嘗試多種前綴模式）
            $prefix = config('cache.prefix', 'laravel_cache');
            $apiKeys = $redis->keys("{$prefix}:api:*");
            $apiKeysAlt = $redis->keys("api:*");  // 嘗試不同的前綴
            
            return [
                'hits' => $hits,
                'misses' => $misses,
                'hit_rate' => $totalOps > 0 ? round(($hits / $totalOps) * 100, 2) : 0,
                'memory_usage' => $info['used_memory_human'] ?? '0B',
                'total_keys' => count($apiKeys),
                'uptime' => (int)($info['uptime_in_seconds'] ?? 0),
                'connected_clients' => (int)($info['connected_clients'] ?? 0),
                // 調試資訊
                'debug' => [
                    'redis_connected' => ($pingResult === true || $pingResult === '+PONG'),
                    'redis_version' => $info['redis_version'] ?? 'unknown',
                    'all_keys_count' => count($allKeys),
                    'all_keys_sample' => array_slice($allKeys, 0, 10),  // 最多顯示10個鍵
                    'api_keys_count' => count($apiKeys),
                    'api_keys_alt_count' => count($apiKeysAlt),
                    'cache_prefix' => $prefix,
                    'db_index' => $info['db0'] ?? null,
                    'config' => [
                        'cache_driver' => config('cache.default'),
                        'redis_client' => config('database.redis.client'),
                    ],
                ]
            ];
        } catch (\Exception $e) {
            // 記錄錯誤
            \Log::error('Redis 統計資料獲取失敗: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            return [
                'hits' => 0,
                'misses' => 0,
                'hit_rate' => 0,
                'memory_usage' => '0B',
                'total_keys' => 0,
                'uptime' => 0,
                'connected_clients' => 0,
                'error' => $e->getMessage(),
                'error_trace' => explode("\n", $e->getTraceAsString()),
            ];
        }
    }

    /**
     * 獲取總快取鍵數
     */
    private function getTotalKeys(): int
    {
        try {
            $redis = Redis::connection()->client();
            $prefix = config('cache.prefix', 'laravel_cache');
            return count($redis->keys("{$prefix}:api:*"));
        } catch (\Exception $e) {
            \Log::error('獲取快取鍵數失敗: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 根據前綴獲取快取鍵
     */
    private function getCacheKeysByPrefix(string $prefix): array
    {
        try {
            $redis = Redis::connection()->client();
            $cachePrefix = config('cache.prefix', 'laravel_cache');
            $fullPrefix = "{$cachePrefix}:{$prefix}";
            return $redis->keys("{$fullPrefix}*") ?: [];
        } catch (\Exception $e) {
            \Log::error('獲取前綴快取鍵失敗: ' . $e->getMessage());
            return [];
        }
    }
}
