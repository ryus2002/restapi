<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class TestDockerNetwork extends Command
{
    protected $signature = 'docker:test-redis';
    protected $description = '測試 Docker Redis 連線';

    public function handle()
    {
        $this->info('開始測試 Docker Redis 連線...');
        
        // 1. 顯示環境變數
        $this->info('環境變數:');
        $this->line("REDIS_HOST: " . env('REDIS_HOST', '未設置'));
        $this->line("REDIS_PORT: " . env('REDIS_PORT', '未設置'));
        $this->line("CACHE_DRIVER: " . env('CACHE_DRIVER', '未設置'));
        
        // 2. 嘗試 Ping Redis 伺服器
        try {
            $this->info('嘗試 Ping Redis 伺服器...');
            $redis = Redis::connection()->client();
            $pingResult = $redis->ping();
            $this->info("Ping 結果: " . ($pingResult === true || $pingResult === '+PONG' ? '成功' : $pingResult));
            
            // 3. 嘗試寫入和讀取資料
            $this->info('嘗試寫入和讀取資料...');
            $testKey = 'artisan_test_' . time();
            $testValue = 'value_' . time();
            $redis->set($testKey, $testValue);
            $readValue = $redis->get($testKey);
            
            $this->info("寫入值: {$testValue}");
            $this->info("讀取值: {$readValue}");
            $this->info("測試結果: " . ($testValue === $readValue ? '成功' : '失敗'));
            
        } catch (\Exception $e) {
            $this->error('Redis 連線測試失敗: ' . $e->getMessage());
            return 1;
        }
        
        // 4. 測試 Cache Facade
        try {
            $this->info('測試 Cache Facade...');
            $cacheKey = 'cache_test_' . time();
            $cacheValue = 'cache_' . time();
            Cache::put($cacheKey, $cacheValue, 60);
            $readCache = Cache::get($cacheKey);
            
            $this->info("Cache 寫入值: {$cacheValue}");
            $this->info("Cache 讀取值: {$readCache}");
            $this->info("Cache 測試結果: " . ($cacheValue === $readCache ? '成功' : '失敗'));
        } catch (\Exception $e) {
            $this->error('Cache 測試失敗: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}