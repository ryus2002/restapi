<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CacheMonitorController;
use App\Services\CacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class CacheMonitorControllerTest extends TestCase
{
    protected $cacheServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 創建 CacheService 的模擬
        $this->cacheServiceMock = Mockery::mock(CacheService::class);
        $this->app->instance(CacheService::class, $this->cacheServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testIndex()
    {
        // 模擬 CacheService 的 getCacheStats 方法
        $this->cacheServiceMock->shouldReceive('getCacheStats')
            ->once()
            ->andReturn([
                'hits' => 150,
                'misses' => 50,
                'hit_rate' => 75,
                'memory_usage' => '1.5M',
                'total_keys' => 3,
                'uptime' => 3600,
                'connected_clients' => 10
            ]);

        // 執行請求
        $response = $this->get('/admin/cache-monitor');

        // 驗證響應
        $response->assertStatus(200);
        $response->assertViewIs('admin.cache-monitor');
        $response->assertViewHas('stats');
    }

    public function testClearAll()
    {
        // 模擬 Cache facade
        Cache::shouldReceive('flush')
            ->once()
            ->andReturn(true);

        // 執行請求
        $response = $this->post('/admin/cache-clear');

        // 驗證響應
        $response->assertStatus(302); // 重定向
        $response->assertSessionHas('message', '所有快取已清除');
    }

    public function testKeyAnalysis()
    {
        // 模擬 CacheService 的方法
        $this->cacheServiceMock->shouldReceive('getCacheStats')
            ->once()
            ->andReturn([
                'hits' => 150,
                'misses' => 50,
                'hit_rate' => 75,
                'memory_usage' => '1.5M',
                'total_keys' => 3,
                'uptime' => 3600,
                'connected_clients' => 10
            ]);

        $this->cacheServiceMock->shouldReceive('analyzeCacheKeys')
            ->once()
            ->andReturn([
                'total_keys' => 3,
                'key_types' => [
                    'api_cache' => 1,
                    'laravel_test' => 1,
                    'direct_test' => 1
                ],
                'key_patterns' => [
                    'api_cache' => 1,
                    'laravel_test' => 1,
                    'direct_test' => 1
                ],
                'ttl_distribution' => [
                    'no_expiry' => 1,
                    'less_than_minute' => 0,
                    '1_to_10_minutes' => 1,
                    '10_to_30_minutes' => 0,
                    '30_to_60_minutes' => 0,
                    '1_to_6_hours' => 1,
                    '6_to_24_hours' => 0,
                    'more_than_day' => 0
                ],
                'size_distribution' => [
                    'small' => 2,
                    'medium' => 1,
                    'large' => 0,
                    'very_large' => 0
                ],
                'sample_keys' => [
                    [
                        'key' => 'api_cache:test1',
                        'type' => 'string',
                        'ttl' => 300,
                        'size' => 500,
                        'preview' => 'test value 1'
                    ],
                    [
                        'key' => 'laravel_test:test2',
                        'type' => 'hash',
                        'ttl' => 3600,
                        'size' => 5000,
                        'fields_count' => 3
                    ],
                    [
                        'key' => 'direct_test_123',
                        'type' => 'string',
                        'ttl' => -1,
                        'size' => 100,
                        'preview' => 'test value 2'
                    ]
                ]
            ]);

        // 執行請求
        $response = $this->get('/admin/cache-key-analysis');

        // 驗證響應
        $response->assertStatus(200);
        $response->assertViewIs('admin.cache-monitor');
        $response->assertViewHas('stats');
        $response->assertViewHas('analysis');
        $response->assertViewHas('showAnalysis', true);
    }
}