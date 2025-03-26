<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class RedisTestController extends Controller
{
    public function testConnection()
    {
        $results = [];
        // 1. 環境設定資訊 - 使用 config() 而不是 env()
        $results['environment'] = [
            'redis_host' => config('database.redis.default.host', '127.0.0.1'),
            'redis_port' => config('database.redis.default.port', 6379),
            'redis_client' => config('database.redis.client', 'phpredis'),
            'cache_driver' => config('cache.default', 'file'),
            'has_password' => !empty(config('database.redis.default.password')),
            'app_container' => gethostname(),
            'php_version' => phpversion(),
        ];
        // 2. PHP 擴展檢查
        $results['php_extensions'] = [
            'redis_extension' => extension_loaded('redis') ? '已安裝' : '未安裝',
            'predis_library' => class_exists('Predis\Client') ? '已安裝' : '未安裝',
        ];
        
        // 3. 嘗試 socket 層面的基本連接 - 使用 config() 而不是 env()
        try {
            $socket = @fsockopen(
                config('database.redis.default.host', '127.0.0.1'),
                config('database.redis.default.port', 6379),
                $errno,
                $errstr,
                2
            );
                
            $results['socket_test'] = [
                'success' => $socket !== false,
                'error_code' => $errno ?? null,
                'error_message' => $errstr ?? null
            ];
            
            if ($socket) {
                fclose($socket);
            }
        } catch (\Exception $e) {
            $results['socket_test'] = [
                'success' => false,
                'exception' => $e->getMessage()
            ];
    }
        
        // 4. 嘗試直接使用 PHP Redis 擴展 - 使用 config() 而不是 env()
        try {
            if (extension_loaded('redis')) {
                $redis = new \Redis();
                $connected = @$redis->connect(
                    config('database.redis.default.host', '127.0.0.1'),
                    config('database.redis.default.port', 6379),
                    2.0
                );
                
                $results['direct_redis_test'] = [
                    'connected' => $connected,
                ];
                
                if ($connected) {
                    // 如果設置了密碼，嘗試認證
                    $password = config('database.redis.default.password');
                    if ($password) {
                        $results['direct_redis_test']['auth'] = $redis->auth($password);
                    }
                    
                    $results['direct_redis_test']['ping'] = $redis->ping();
                    $testKey = 'direct_test_' . time();
                    $redis->set($testKey, 'test_value');
                    $results['direct_redis_test']['get_set'] = $redis->get($testKey) === 'test_value';
                }
            } else {
                $results['direct_redis_test'] = [
                    'message' => 'PHP Redis 擴展未安裝'
                ];
            }
        } catch (\Exception $e) {
            $results['direct_redis_test'] = [
                'connected' => false,
                'exception' => $e->getMessage()
            ];
        }
        
        // 5. 嘗試使用 Laravel Redis Facade
        try {
            $laravelRedis = Redis::connection()->client();
            $pingResult = false;
            
            try {
                $pingResult = $laravelRedis->ping();
            } catch (\Exception $e) {
                $results['laravel_redis']['ping_exception'] = $e->getMessage();
            }
            
            $results['laravel_redis'] = [
                'connected' => true,
                'ping' => $pingResult
            ];
            
            // 嘗試寫入/讀取操作
            if ($pingResult) {
                $testKey = 'laravel_test_' . time();
                $laravelRedis->set($testKey, 'laravel_value');
                $results['laravel_redis']['get_set'] = $laravelRedis->get($testKey) === 'laravel_value';
            }
        } catch (\Exception $e) {
            $results['laravel_redis'] = [
                'connected' => false,
                'exception' => $e->getMessage()
            ];
        }
        
        // 添加一个简单的 Redis 连接状态标志
        $results['debug'] = [
            'redis_connected' => isset($results['laravel_redis']['ping']) && $results['laravel_redis']['ping']
        ];
        
        // 6. 診斷建議
        $results['diagnosis'] = $this->getDiagnosis($results);
        
        return response()->json($results);
    }
    
    private function getDiagnosis($results)
    {
        $diagnosis = [];
        
        // 更精確地檢查 Socket 連接
        if (!$results['socket_test']['success']) {
            $diagnosis[] = '❌ 基本網路連接失敗：無法連接到 ' . $results['environment']['redis_host'] . ':' . $results['environment']['redis_port'];
            if (isset($results['socket_test']['error_message'])) {
                $diagnosis[] = '連接錯誤: ' . $results['socket_test']['error_message'];
            }
            $diagnosis[] = '推薦：檢查 Redis 服務是否運行、網絡配置以及防火牆設置';
        }
        
        // 檢查 Predis 庫
        if ($results['environment']['redis_client'] === 'predis' && $results['php_extensions']['predis_library'] !== '已安裝') {
            $diagnosis[] = '❌ REDIS_CLIENT 設置為 predis，但 Predis 庫未安裝';
            $diagnosis[] = '推薦：執行 composer require predis/predis';
        }
        
        // 檢查 PHP Redis 擴展
        if ($results['environment']['redis_client'] === 'phpredis' && $results['php_extensions']['redis_extension'] !== '已安裝') {
            $diagnosis[] = '❌ PHP Redis 擴展未安裝，但配置為使用 phpredis 客戶端';
            $diagnosis[] = '推薦：安裝 PHP Redis 擴展或在 .env 中設置 REDIS_CLIENT=predis 並安裝 predis/predis 套件';
        }
        
        // 檢查 Laravel Redis 連接
        if (isset($results['laravel_redis'])) {
            if (!$results['laravel_redis']['connected']) {
                $diagnosis[] = '❌ Laravel Redis Facade 無法連接到 Redis 服務器';
                if (isset($results['laravel_redis']['exception'])) {
                    $diagnosis[] = '錯誤信息：' . $results['laravel_redis']['exception'];
                }
            } elseif (!isset($results['laravel_redis']['ping']) || !$results['laravel_redis']['ping']) {
                $diagnosis[] = '❌ Redis 連接建立但 PING 失敗';
                if (isset($results['laravel_redis']['ping_exception'])) {
                    $diagnosis[] = '錯誤信息：' . $results['laravel_redis']['ping_exception'];
                }
                $diagnosis[] = '可能是身份認證問題，請確認 REDIS_PASSWORD 是否正確';
            }
        }
    
        // 檢查直接連接
        if (isset($results['direct_redis_test'])) {
            if (isset($results['direct_redis_test']['connected']) && !$results['direct_redis_test']['connected']) {
                $diagnosis[] = '❌ 直接使用 ' . ($results['environment']['redis_client'] === 'phpredis' ? 'PHP Redis' : 'Predis') . ' 連接失敗';
                if (isset($results['direct_redis_test']['exception'])) {
                    $diagnosis[] = '錯誤信息：' . $results['direct_redis_test']['exception'];
                }
            }
        }
        
        // 如果沒有檢測到問題，但仍有訪問問題
        if (empty($diagnosis)) {
            $connected = false;
            
            // 檢查是否有任何連接測試成功
            if ((isset($results['socket_test']['success']) && $results['socket_test']['success'])) {
                $diagnosis[] = '✅ 網絡層連接測試成功';
                $connected = true;
            }
            
            // 檢查 Laravel Redis Facade 連接
            if (isset($results['laravel_redis']['ping']) && $results['laravel_redis']['ping']) {
                $diagnosis[] = '✅ Laravel Redis Facade 連接測試成功';
                $connected = true;
            }
            
            if ($connected) {
                $diagnosis[] = '⚠️ 基本連接測試成功，但仍可能有權限或配置問題';
            } else {
                $diagnosis[] = '❌ 所有連接方式均失敗，建議檢查完整診斷報告';
            }
        }
        
        return $diagnosis;
    }
}